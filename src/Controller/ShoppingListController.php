<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\ShoppingList;
use App\Entity\ShoppingListRecipe;
use App\Repository\ShoppingListRepository;
use App\Service\CurrentUserProfileProvider;
use App\Service\Shopping\IngredientClassifier;
use App\Service\Shopping\ShoppingListBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/shopping-list')]
final class ShoppingListController extends AbstractController
{
    #[Route('', name: 'app_shopping_list_show', methods: ['GET'])]
    public function show(
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
    ): Response {
        $shoppingList = $repository->findActiveForProfile($profileProvider->getRequiredProfile());

        return $this->render('shopping_list/show.html.twig', [
            'shoppingList' => $shoppingList,
            'itemsByCategory' => $shoppingList === null ? [] : $this->groupItemsByCategory($shoppingList),
        ]);
    }

    #[Route(
        '/add-recipe/{id}',
        name: 'app_shopping_list_add_recipe',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    public function addRecipe(
        Recipe $recipe,
        Request $request,
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
        ShoppingListBuilder $builder,
        EntityManagerInterface $entityManager,
    ): Response {
        $csrfTokenId = 'add-recipe-to-shopping-list-'.$recipe->getId();
        if (!$this->isCsrfTokenValid($csrfTokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
        $preparationCount = max(1, min(99, $request->request->getInt('preparationCount', 1)));
        $profile = $profileProvider->getRequiredProfile();
        $shoppingList = $repository->findActiveForProfile($profile) ?? (new ShoppingList())->setProfile($profile);

        $existingSelection = null;
        foreach ($shoppingList->getRecipeSelections() as $selection) {
            if ($selection->getRecipe() === $recipe) {
                $existingSelection = $selection;
                break;
            }
        }
        if ($existingSelection !== null) {
            $existingSelection->setPreparationCount($existingSelection->getPreparationCount() + $preparationCount);
        } else {
            $shoppingList->addRecipeSelection(
                (new ShoppingListRecipe())
                    ->setRecipe($recipe)
                    ->setRecipeTitle($recipe->getTitle())
                    ->setIngredientSnapshot($recipe->getIngredients())
                    ->setPreparationCount($preparationCount),
            );
        }
        $shoppingList->markRecipeAdded();
        $builder->rebuild($shoppingList);
        $entityManager->persist($shoppingList);
        $entityManager->flush();
        $this->addFlash('success', sprintf('« %s » a été ajoutée à la liste de courses.', $recipe->getTitle()));

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route(
        '/recipe/{id}/quantity',
        name: 'app_shopping_list_recipe_quantity',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    public function updateRecipeQuantity(
        ShoppingListRecipe $selection,
        Request $request,
        CurrentUserProfileProvider $profileProvider,
        ShoppingListBuilder $builder,
        EntityManagerInterface $entityManager,
    ): Response {
        $shoppingList = $this->requireOwnedList($selection->getShoppingList(), $profileProvider);
        $csrfTokenId = 'shopping-list-recipe-'.$selection->getId();
        if (!$this->isCsrfTokenValid($csrfTokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $selection->setPreparationCount(max(1, min(99, $request->request->getInt('preparationCount', 1))));
        $builder->rebuild($shoppingList);
        $entityManager->flush();

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route(
        '/recipe/{id}/delete',
        name: 'app_shopping_list_recipe_delete',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    public function removeRecipe(
        ShoppingListRecipe $selection,
        Request $request,
        CurrentUserProfileProvider $profileProvider,
        ShoppingListBuilder $builder,
        EntityManagerInterface $entityManager,
    ): Response {
        $shoppingList = $this->requireOwnedList($selection->getShoppingList(), $profileProvider);
        $csrfTokenId = 'shopping-list-recipe-'.$selection->getId();
        if (!$this->isCsrfTokenValid($csrfTokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $shoppingList->removeRecipeSelection($selection);
        $entityManager->remove($selection);
        $builder->rebuild($shoppingList);
        $entityManager->flush();

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route('/item/add', name: 'app_shopping_list_item_add', methods: ['POST'])]
    public function addManualItem(
        Request $request,
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
        IngredientClassifier $classifier,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('shopping-list-add-item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $name = trim($request->request->getString('name'));
        if ($name === '') {
            $this->addFlash('error', 'Indique un nom pour le produit.');

            return $this->redirectToRoute('app_shopping_list_show');
        }
        $profile = $profileProvider->getRequiredProfile();
        $shoppingList = $repository->findActiveForProfile($profile) ?? (new ShoppingList())->setProfile($profile);
        $items = $shoppingList->getItems();
        $requestedCategory = trim($request->request->getString('category'));
        $items[] = [
            'key' => 'manual-'.bin2hex(random_bytes(8)),
            'name' => $name,
            'quantity' => max(0.0, (float) str_replace(',', '.', $request->request->getString('quantity', '1'))),
            'calculatedQuantity' => 0.0,
            'unit' => trim($request->request->getString('unit')),
            'category' => $requestedCategory !== '' ? $requestedCategory : $classifier->classify($name),
            'categoryOverridden' => $requestedCategory !== '',
            'checked' => false,
            'manual' => true,
        ];
        $shoppingList->setItems($items)->touch();
        $entityManager->persist($shoppingList);
        $entityManager->flush();

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route('/item/{key}', name: 'app_shopping_list_item_update', methods: ['POST'])]
    public function updateItem(
        string $key,
        Request $request,
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        $shoppingList = $repository->findActiveForProfile($profileProvider->getRequiredProfile());
        $csrfTokenId = 'shopping-list-item-'.$key;
        if ($shoppingList === null || !$this->isCsrfTokenValid(
            $csrfTokenId,
            (string) $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }
        $items = $shoppingList->getItems();
        foreach ($items as $index => &$item) {
            if ($item['key'] !== $key) {
                continue;
            }
            if ($request->request->has('delete')) {
                if (!$item['manual']) {
                    $shoppingList->excludeGeneratedItem($key);
                }
                unset($items[$index]);
                break;
            }
            $item['checked'] = $request->request->getBoolean('checked');
            $item['name'] = trim($request->request->getString('name', $item['name'])) ?: $item['name'];
            $requestedQuantity = $request->request->getString('quantity', (string) $item['quantity']);
            $item['quantity'] = max(0.0, (float) str_replace(',', '.', $requestedQuantity));
            $item['unit'] = trim($request->request->getString('unit', $item['unit']));
            $requestedCategory = trim($request->request->getString('category', $item['category'])) ?: 'Autres';
            if ($requestedCategory !== $item['category']) {
                $item['category'] = $requestedCategory;
                $item['categoryOverridden'] = true;
            }
            break;
        }
        unset($item);
        $shoppingList->setItems(array_values($items))->touch();
        $entityManager->flush();

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route('/complete', name: 'app_shopping_list_complete', methods: ['POST'])]
    public function complete(
        Request $request,
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        $shoppingList = $repository->findActiveForProfile($profileProvider->getRequiredProfile());
        $csrfTokenId = 'complete-shopping-list-'.$shoppingList?->getId();
        if ($shoppingList !== null && $this->isCsrfTokenValid(
            $csrfTokenId,
            (string) $request->request->get('_token'),
        )) {
            $shoppingList->setActive(false)->touch();
            $entityManager->flush();
            $this->addFlash(
                'success',
                'La liste a été terminée. La prochaine recette commencera une nouvelle liste.',
            );
        }

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route('/cancel', name: 'app_shopping_list_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        $shoppingList = $repository->findActiveForProfile($profileProvider->getRequiredProfile());
        if ($shoppingList === null) {
            throw $this->createNotFoundException('Aucune liste active à annuler.');
        }
        if (!$this->isCsrfTokenValid(
            'cancel-shopping-list-'.$shoppingList->getId(),
            (string) $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $entityManager->remove($shoppingList);
        $entityManager->flush();
        $this->addFlash('success', 'La liste de courses en cours a été annulée et supprimée.');

        return $this->redirectToRoute('app_shopping_list_show');
    }

    #[Route('/pdf', name: 'app_shopping_list_pdf', methods: ['GET'])]
    public function downloadPdf(
        ShoppingListRepository $repository,
        CurrentUserProfileProvider $profileProvider,
    ): Response {
        $shoppingList = $repository->findActiveForProfile($profileProvider->getRequiredProfile());
        if ($shoppingList === null) {
            throw $this->createNotFoundException('Aucune liste active.');
        }
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($this->renderView('shopping_list/pdf.html.twig', [
            'shoppingList' => $shoppingList,
            'itemsByCategory' => $this->groupItemsByCategory($shoppingList),
        ]));
        $pdf->setPaper('A4');
        $pdf->render();
        return new Response($pdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="liste-de-courses-%s.pdf"',
                $shoppingList->getLastRecipeAddedAt()->format('Y-m-d'),
            ),
        ]);
    }

    private function requireOwnedList(
        ?ShoppingList $shoppingList,
        CurrentUserProfileProvider $profileProvider,
    ): ShoppingList {
        $isOwnedActiveList = $shoppingList !== null
            && $shoppingList->getProfile() === $profileProvider->getRequiredProfile()
            && $shoppingList->isActive();

        if (!$isOwnedActiveList) {
            throw $this->createAccessDeniedException();
        }

        return $shoppingList;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function groupItemsByCategory(ShoppingList $shoppingList): array
    {
        $groupedItems = [];
        foreach ($shoppingList->getItems() as $item) {
            $groupedItems[$item['category']][] = $item;
        }
        ksort($groupedItems);

        return $groupedItems;
    }
}
