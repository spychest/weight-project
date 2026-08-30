<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/activity/new', name: 'app_activity_new')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ProfileRepository $profileRepository,
    ): Response {
        $profile = $profileRepository->findFirstProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        $activity = new Activity();

        $activity
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable());

        $form = $this->createForm(ActivityType::class, $activity);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('activity/new.html.twig', [
            'form' => $form,
        ]);
    }
}
