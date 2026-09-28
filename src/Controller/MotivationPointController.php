<?php

namespace App\Controller;

use App\Entity\MotivationPoint;
use App\Form\MotivationPointType;
use App\Repository\MotivationPointRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/{id}/move/{direction}', name: 'app_motivation_point_move', requirements: ['id' => '\d+', 'direction' => 'up|down'], methods: ['POST'])]
    public function move(
        MotivationPoint $motivationPoint,
        string $direction,
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        MotivationPointRepository $motivationPointRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->assertOwnership($motivationPoint, $currentUserProfileProvider);
        if (!$this->isCsrfTokenValid('move-motivation-point-'.$motivationPoint->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $adjacentPoint = $motivationPointRepository->findAdjacent($motivationPoint, $direction);
        if ($adjacentPoint !== null) {
            $currentPosition = $motivationPoint->getPosition();
            $motivationPoint->setPosition($adjacentPoint->getPosition());
            $adjacentPoint->setPosition($currentPosition);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_motivation_point_index');
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
