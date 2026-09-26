<?php

namespace App\Controller;

use App\DTO\Admin\AdminUserData;
use App\Entity\IngredientCatalogItem;
use App\Entity\User;
use App\Repository\IngredientCatalogItemRepository;
use App\Repository\UserRepository;
use App\Service\Admin\AdminStatisticsService;
use App\Service\Shopping\IngredientClassifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use JsonException;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    private const ITEMS_PER_PAGE = 25;

    #[Route('', name: 'app_admin_index', methods: ['GET'])]
    public function index(
        AdminStatisticsService $statisticsService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        return $this->render('admin/index.html.twig', [
            'statistics' => $statisticsService->getStatistics(),
            'adminItemsPerPage' => self::ITEMS_PER_PAGE,
            'catalogCsrfToken' => $csrfTokenManager
                ->getToken('admin-ingredient-catalog')
                ->getValue(),
            'userAdministrationCsrfToken' => $csrfTokenManager
                ->getToken('admin-user-management')
                ->getValue(),
        ]);
    }

    #[Route('/api/ingredients', name: 'app_admin_ingredient_list', methods: ['GET'])]
    public function listIngredients(
        Request $request,
        IngredientCatalogItemRepository $repository,
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $searchTerm = trim($request->query->getString('search'));
        $category = trim($request->query->getString('category'));
        $result = $repository->searchPaginated(
            $searchTerm,
            $category,
            $page,
            self::ITEMS_PER_PAGE,
        );
        $pageCount = max(1, (int) ceil($result['total'] / self::ITEMS_PER_PAGE));

        if ($page > $pageCount) {
            $page = $pageCount;
            $result = $repository->searchPaginated(
                $searchTerm,
                $category,
                $page,
                self::ITEMS_PER_PAGE,
            );
        }

        return $this->json([
            'items' => array_map($this->serializeCatalogItem(...), $result['items']),
            'categories' => $repository->findDistinctCategories(),
            'pagination' => [
                'page' => $page,
                'pageCount' => $pageCount,
                'total' => $result['total'],
                'itemsPerPage' => self::ITEMS_PER_PAGE,
            ],
        ]);
    }

    #[Route('/api/users', name: 'app_admin_user_list', methods: ['GET'])]
    public function listUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $searchTerm = trim($request->query->getString('search'));
        $role = $request->query->getString('role');
        $status = $request->query->getString('status');
        $result = $userRepository->searchPaginated(
            $searchTerm,
            $role,
            $status,
            $page,
            self::ITEMS_PER_PAGE,
        );
        $pageCount = max(1, (int) ceil($result['total'] / self::ITEMS_PER_PAGE));

        if ($page > $pageCount) {
            $page = $pageCount;
            $result = $userRepository->searchPaginated(
                $searchTerm,
                $role,
                $status,
                $page,
                self::ITEMS_PER_PAGE,
            );
        }

        return $this->json([
            'items' => array_map(
                static fn (User $user): AdminUserData => AdminUserData::fromUser($user),
                $result['items'],
            ),
            'pagination' => [
                'page' => $page,
                'pageCount' => $pageCount,
                'total' => $result['total'],
                'itemsPerPage' => self::ITEMS_PER_PAGE,
            ],
        ]);
    }

    #[Route(
        '/api/users/{id}',
        name: 'app_admin_user_update',
        requirements: ['id' => '\d+'],
        methods: ['PATCH'],
    )]
    public function updateUser(
        User $user,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->validateCsrfToken($request, 'admin-user-management');
        $payload = $this->decodePayload($request);
        $authenticatedAdministrator = $this->getUser();

        if (array_key_exists('administrator', $payload)) {
            $shouldBeAdministrator = $payload['administrator'] === true;
            $isAdministrator = in_array('ROLE_ADMIN', $user->getRoles(), true);

            if ($user === $authenticatedAdministrator && !$shouldBeAdministrator) {
                return $this->json(['error' => 'Tu ne peux pas retirer ton propre rôle administrateur.'], 422);
            }
            if (
                $isAdministrator
                && !$shouldBeAdministrator
                && !$user->isSuspended()
                && $userRepository->countActiveAdministrators() <= 1
            ) {
                return $this->json(['error' => 'Le site doit conserver au moins un administrateur actif.'], 422);
            }

            $roles = array_values(array_filter(
                $user->getRoles(),
                static fn (string $role): bool => $role !== 'ROLE_ADMIN',
            ));
            if ($shouldBeAdministrator) {
                $roles[] = 'ROLE_ADMIN';
            }
            $user->setRoles(array_values(array_unique($roles)));
        }

        if (array_key_exists('suspended', $payload)) {
            $shouldBeSuspended = $payload['suspended'] === true;
            if ($user === $authenticatedAdministrator && $shouldBeSuspended) {
                return $this->json(['error' => 'Tu ne peux pas suspendre ton propre compte.'], 422);
            }
            if (
                $shouldBeSuspended
                && !$user->isSuspended()
                && in_array('ROLE_ADMIN', $user->getRoles(), true)
                && $userRepository->countActiveAdministrators() <= 1
            ) {
                return $this->json(['error' => 'Le site doit conserver au moins un administrateur actif.'], 422);
            }

            if ($shouldBeSuspended) {
                $reason = is_string($payload['suspensionReason'] ?? null)
                    ? mb_substr(trim($payload['suspensionReason']), 0, 500)
                    : null;
                $user->suspend($reason);
            } else {
                $user->reactivate();
            }
        }

        $entityManager->flush();

        return $this->json(['item' => AdminUserData::fromUser($user)]);
    }

    #[Route(
        '/api/users/{id}',
        name: 'app_admin_user_delete',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'],
    )]
    public function deleteUser(
        User $user,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->validateCsrfToken($request, 'admin-user-management');
        if ($user === $this->getUser()) {
            return $this->json(['error' => 'Tu ne peux pas supprimer ton propre compte depuis l’administration.'], 422);
        }
        if (
            !$user->isSuspended()
            && in_array('ROLE_ADMIN', $user->getRoles(), true)
            && $userRepository->countActiveAdministrators() <= 1
        ) {
            return $this->json(['error' => 'Le site doit conserver au moins un administrateur actif.'], 422);
        }

        $payload = $this->decodePayload($request);
        $expectedDisplayName = $user->getProfile()?->getDisplayName() ?? 'Profil non créé';
        if (($payload['confirmationDisplayName'] ?? null) !== $expectedDisplayName) {
            return $this->json(['error' => 'Le nom de confirmation ne correspond pas.'], 422);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    #[Route('/api/ingredients', name: 'app_admin_ingredient_create', methods: ['POST'])]
    public function createIngredient(
        Request $request,
        IngredientClassifier $classifier,
        IngredientCatalogItemRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->validateCatalogCsrfToken($request);
        $payload = $this->decodePayload($request);
        $canonicalName = trim((string) ($payload['canonicalName'] ?? ''));
        $normalizedName = $classifier->normalizeName($canonicalName);
        $category = trim((string) ($payload['category'] ?? ''));

        if ($canonicalName === '' || $category === '') {
            return $this->json(['error' => 'Le nom et la catégorie sont obligatoires.'], 422);
        }
        if ($repository->findOneBy(['normalizedName' => $normalizedName]) !== null) {
            return $this->json(['error' => 'Cet aliment existe déjà dans le catalogue.'], 409);
        }

        $catalogItem = (new IngredientCatalogItem())
            ->setCanonicalName($canonicalName)
            ->setNormalizedName($normalizedName)
            ->setCategory($category)
            ->setAliases($this->normalizeAliases($payload['aliases'] ?? []));
        $entityManager->persist($catalogItem);
        $entityManager->flush();

        return $this->json(['item' => $this->serializeCatalogItem($catalogItem)], 201);
    }

    #[Route(
        '/api/ingredients/{id}',
        name: 'app_admin_ingredient_update',
        requirements: ['id' => '\\d+'],
        methods: ['PUT'],
    )]
    public function updateIngredient(
        IngredientCatalogItem $catalogItem,
        Request $request,
        IngredientClassifier $classifier,
        IngredientCatalogItemRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->validateCatalogCsrfToken($request);
        $payload = $this->decodePayload($request);
        $canonicalName = trim((string) ($payload['canonicalName'] ?? ''));
        $normalizedName = $classifier->normalizeName($canonicalName);
        $category = trim((string) ($payload['category'] ?? ''));

        if ($canonicalName === '' || $category === '') {
            return $this->json(['error' => 'Le nom et la catégorie sont obligatoires.'], 422);
        }
        $existingItem = $repository->findOneBy(['normalizedName' => $normalizedName]);
        if ($existingItem !== null && $existingItem !== $catalogItem) {
            return $this->json(['error' => 'Cet aliment existe déjà dans le catalogue.'], 409);
        }

        $catalogItem
            ->setCanonicalName($canonicalName)
            ->setNormalizedName($normalizedName)
            ->setCategory($category)
            ->setAliases($this->normalizeAliases($payload['aliases'] ?? []));
        $entityManager->flush();

        return $this->json(['item' => $this->serializeCatalogItem($catalogItem)]);
    }

    #[Route(
        '/api/ingredients/{id}',
        name: 'app_admin_ingredient_delete',
        requirements: ['id' => '\\d+'],
        methods: ['DELETE'],
    )]
    public function deleteIngredient(
        IngredientCatalogItem $catalogItem,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->validateCatalogCsrfToken($request);
        $entityManager->remove($catalogItem);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    private function validateCatalogCsrfToken(Request $request): void
    {
        $this->validateCsrfToken($request, 'admin-ingredient-catalog');
    }

    private function validateCsrfToken(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->headers->get('X-CSRF-TOKEN'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    /** @return list<string> */
    private function normalizeAliases(mixed $aliases): array
    {
        if (is_string($aliases)) {
            $aliases = explode(',', $aliases);
        }
        if (!is_array($aliases)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $alias): string => trim((string) $alias),
            $aliases,
        )));
    }

    /** @return array{id: int|null, canonicalName: string, category: string, aliases: list<string>} */
    private function serializeCatalogItem(IngredientCatalogItem $catalogItem): array
    {
        return [
            'id' => $catalogItem->getId(),
            'canonicalName' => $catalogItem->getCanonicalName(),
            'category' => $catalogItem->getCategory(),
            'aliases' => $catalogItem->getAliases(),
        ];
    }
}
