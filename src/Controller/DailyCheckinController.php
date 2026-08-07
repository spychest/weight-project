<?php

namespace App\Controller;

use App\Entity\DailyCheckin;
use App\Entity\Profile;
use App\Form\DailyCheckinType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DailyCheckinController extends AbstractController
{
    #[Route('/daily-checkin/new', name: 'app_daily_checkin_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $entityManager
            ->getRepository(Profile::class)
            ->findOneBy([]);

        $checkin = new DailyCheckin();

        $checkin
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable());

        $form = $this->createForm(DailyCheckinType::class, $checkin);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($checkin);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('daily_checkin/new.html.twig', [
            'form' => $form,
        ]);
    }
}