<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Profile;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/activity/new', name: 'app_activity_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $entityManager
            ->getRepository(Profile::class)
            ->findOneBy([]);

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