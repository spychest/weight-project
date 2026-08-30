<?php

namespace App\Controller;

use App\Entity\Milestone;
use App\Form\MilestoneType;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MilestoneController extends AbstractController
{
    #[Route('/milestone/new', name: 'app_milestone_new')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        $milestone = new Milestone();
        $milestone->setProfile($profile);

        $form = $this->createForm(MilestoneType::class, $milestone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($milestone);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('milestone/new.html.twig', [
            'form' => $form,
        ]);
    }
}
