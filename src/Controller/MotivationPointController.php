<?php

namespace App\Controller;

use App\Entity\MotivationPoint;
use App\Form\MotivationPointType;
use App\Repository\MotivationPointRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/motivation-points')]
final class MotivationPointController extends AbstractController
{
    #[Route('', name: 'app_motivation_point_index', methods: ['GET'])]
    public function index(
        MotivationPointRepository $motivationPointRepository,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        return $this->render('motivation_point/index.html.twig', [
            'motivationPoints' => $motivationPointRepository->findForProfile($currentUserProfileProvider->getRequiredProfile()),
        ]);
    }

    #[Route('/new', name: 'app_motivation_point_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_motivation_point_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createOrEdit(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        MotivationPointRepository $motivationPointRepository,
        EntityManagerInterface $entityManager,
        ?MotivationPoint $motivationPoint = null,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();
        $isEditMode = $motivationPoint !== null;

        if ($motivationPoint !== null && $motivationPoint->getProfile() !== $profile) {
            throw $this->createNotFoundException();
        }

        if ($motivationPoint === null) {
            $motivationPoint = (new MotivationPoint())
                ->setProfile($profile)
                ->setPosition($motivationPointRepository->getNextPosition($profile));
        }

        $form = $this->createForm(MotivationPointType::class, $motivationPoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($motivationPoint);
            $entityManager->flush();
            $this->addFlash('success', $isEditMode ? 'La motivation a été modifiée.' : 'La motivation a été ajoutée.');

            return $this->redirectToRoute('app_motivation_point_index');
        }

        return $this->render('motivation_point/form.html.twig', [
            'form' => $form,
            'editMode' => $isEditMode,
        ]);
    }

    #[Route('/reorder', name: 'app_motivation_point_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        MotivationPointRepository $motivationPointRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('reorder-motivation-points', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $submittedIdentifiers = $request->toArray()['orderedIds'] ?? null;
        if (!is_array($submittedIdentifiers)) {
            return $this->json(['message' => 'Ordre invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $motivationPoints = $motivationPointRepository->findForProfile(
            $currentUserProfileProvider->getRequiredProfile(),
        );
        $motivationPointsByIdentifier = [];
        foreach ($motivationPoints as $motivationPoint) {
            $motivationPointsByIdentifier[(int) $motivationPoint->getId()] = $motivationPoint;
        }

        $orderedIdentifiers = array_map(static fn (mixed $identifier): int => (int) $identifier, $submittedIdentifiers);
        $expectedIdentifiers = array_keys($motivationPointsByIdentifier);
        $identifiersToValidate = $orderedIdentifiers;
        sort($expectedIdentifiers);
        sort($identifiersToValidate);

        if ($identifiersToValidate !== $expectedIdentifiers || count(array_unique($orderedIdentifiers)) !== count($orderedIdentifiers)) {
            return $this->json(['message' => 'La liste des motivations est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($orderedIdentifiers as $position => $identifier) {
            $motivationPointsByIdentifier[$identifier]->setPosition($position + 1);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Ordre enregistré.']);
    }

    #[Route('/{id}/delete', name: 'app_motivation_point_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        MotivationPoint $motivationPoint,
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->assertOwnership($motivationPoint, $currentUserProfileProvider);
        if (!$this->isCsrfTokenValid('delete-motivation-point-'.$motivationPoint->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $entityManager->remove($motivationPoint);
        $entityManager->flush();
        $this->addFlash('success', 'La motivation a été supprimée.');

        return $this->redirectToRoute('app_motivation_point_index');
    }

    private function assertOwnership(
        MotivationPoint $motivationPoint,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): void {
        if ($motivationPoint->getProfile() !== $currentUserProfileProvider->getRequiredProfile()) {
            throw $this->createNotFoundException();
        }
    }
}
