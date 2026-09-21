<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\RecipeView;
use App\Form\RecipeType;
use App\Pagination\PaginatedResult;
use App\Repository\RecipeRepository;
use App\Repository\RecipeViewRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/recipes')]
final class RecipeController extends AbstractController
{
    #[Route('', name: 'app_recipe_index', methods: ['GET'])]
    public function index(Request $request, RecipeRepository $recipeRepository): Response
    {
        return $this->render('recipe/index.html.twig', [
            'recipesPagination' => $recipeRepository->paginatePublished(
                $request->query->getInt('page', 1),
                PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
            ),
        ]);
    }

    #[Route('/mine', name: 'app_recipe_manage', methods: ['GET'])]
    public function manage(
        Request $request,
        RecipeRepository $recipeRepository,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        return $this->render('recipe/manage.html.twig', [
            'recipesPagination' => $recipeRepository->paginateForProfile(
                $currentUserProfileProvider->getRequiredProfile(),
                $request->query->getInt('page', 1),
                PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
            ),
        ]);
    }

    #[Route('/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_recipe_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
        SluggerInterface $slugger,
        ?Recipe $recipe = null,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();
        $isEditMode = $recipe !== null;

        if ($isEditMode && $recipe->getProfile() !== $profile) {
            throw $this->createNotFoundException();
        }

        if ($recipe === null) {
            $recipe = (new Recipe())->setProfile($profile);
        }

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedPhoto */
            $uploadedPhoto = $form->get('photo')->getData();
            if ($uploadedPhoto !== null) {
                $this->replaceRecipePhoto($recipe, $uploadedPhoto, $slugger);
            }

            $recipe->touch();
            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash('success', $isEditMode ? 'La recette a été modifiée.' : 'La recette a été publiée.');

            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('recipe/form.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
            'editMode' => $isEditMode,
        ]);
    }

    #[Route('/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Recipe $recipe,
        CurrentUserProfileProvider $currentUserProfileProvider,
        RecipeViewRepository $recipeViewRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();

        if ($recipe->getProfile() !== $profile && $recipeViewRepository->findOneBy(['recipe' => $recipe, 'profile' => $profile]) === null) {
            $entityManager->persist((new RecipeView())->setRecipe($recipe)->setProfile($profile));
            $entityManager->flush();
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'isOwner' => $recipe->getProfile() === $profile,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_recipe_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadPdf(Recipe $recipe, SluggerInterface $slugger): Response
    {
        $photoDataUri = null;
        if ($recipe->getPhotoFilename() !== null) {
            $photoPath = $this->getRecipePhotoDirectory().DIRECTORY_SEPARATOR.$recipe->getPhotoFilename();
            if (is_file($photoPath)) {
                $mimeType = mime_content_type($photoPath) ?: 'image/jpeg';
                $photoDataUri = sprintf('data:%s;base64,%s', $mimeType, base64_encode((string) file_get_contents($photoPath)));
            }
        }

        if ($photoDataUri === null) {
            $defaultIllustrationPath = (string) $this->getParameter('kernel.project_dir').'/public/images/recipe-default-illustration.svg';
            $photoDataUri = sprintf(
                'data:image/svg+xml;base64,%s',
                base64_encode((string) file_get_contents($defaultIllustrationPath)),
            );
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $pdfDocument = new Dompdf($options);
        $pdfDocument->loadHtml($this->renderView('recipe/pdf.html.twig', [
            'recipe' => $recipe,
            'photoDataUri' => $photoDataUri,
            'pdfIcons' => $this->getRecipePdfIconDataUris(),
        ]));
        $pdfDocument->setPaper('A4');
        $pdfDocument->render();
        $pdfDocument->getCanvas()->page_text(
            500,
            814,
            'Page {PAGE_NUM} / {PAGE_COUNT}',
            $pdfDocument->getFontMetrics()->getFont('DejaVu Sans'),
            7,
            [0.29, 0.39, 0.35],
        );

        return new Response($pdfDocument->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="recette-%s.pdf"', strtolower($slugger->slug($recipe->getTitle())->toString())),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_recipe_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Recipe $recipe,
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($recipe->getProfile() !== $currentUserProfileProvider->getRequiredProfile()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete-recipe-'.$recipe->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $photoFilename = $recipe->getPhotoFilename();
        $entityManager->remove($recipe);
        $entityManager->flush();
        if ($photoFilename !== null) {
            (new Filesystem())->remove($this->getRecipePhotoDirectory().DIRECTORY_SEPARATOR.$photoFilename);
        }
        $this->addFlash('success', 'La recette a été supprimée.');

        return $this->redirectToRoute('app_recipe_manage');
    }

    private function replaceRecipePhoto(Recipe $recipe, UploadedFile $uploadedPhoto, SluggerInterface $slugger): void
    {
        $photoDirectory = $this->getRecipePhotoDirectory();
        (new Filesystem())->mkdir($photoDirectory);
        $oldPhotoFilename = $recipe->getPhotoFilename();
        $safeTitle = strtolower($slugger->slug($recipe->getTitle())->toString()) ?: 'recette';
        $newPhotoFilename = sprintf('%s-%s.%s', $safeTitle, bin2hex(random_bytes(6)), $uploadedPhoto->guessExtension() ?: 'jpg');
        $uploadedPhoto->move($photoDirectory, $newPhotoFilename);
        $recipe->setPhotoFilename($newPhotoFilename);

        if ($oldPhotoFilename !== null) {
            (new Filesystem())->remove($photoDirectory.DIRECTORY_SEPARATOR.$oldPhotoFilename);
        }
    }

    private function getRecipePhotoDirectory(): string
    {
        return (string) $this->getParameter('kernel.project_dir').'/public/uploads/recipes';
    }

    /** @return array<string, string> */
    private function getRecipePdfIconDataUris(): array
    {
        $iconDirectory = (string) $this->getParameter('kernel.project_dir').'/public/images/pdf';
        $iconNames = ['clock', 'pot', 'cutlery', 'basket', 'utensils', 'list', 'bulb'];
        $iconDataUris = [];

        foreach ($iconNames as $iconName) {
            $iconDataUris[$iconName] = sprintf(
                'data:image/svg+xml;base64,%s',
                base64_encode((string) file_get_contents($iconDirectory.'/'.$iconName.'.svg')),
            );
        }

        return $iconDataUris;
    }
}
