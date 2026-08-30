<?php

namespace App\Controller;

use App\Entity\DailyCheckin;
use App\Entity\Profile;
use App\Form\DailyCheckinType;
use App\Repository\DailyCheckinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DailyCheckinController extends AbstractController
{
    #[Route('/daily-checkin/new', name: 'app_daily_checkin_new')]
    #[Route('/daily-checkin/new/{id}', name: 'app_daily_checkin_edit')]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        ?DailyCheckin $dailyCheckin = null,
    ): Response {
        $isEditMode = $dailyCheckin !== null;

        if ($dailyCheckin === null) {
            $profile = $entityManager
                ->getRepository(Profile::class)
                ->findOneBy([]);

            $dailyCheckin = new DailyCheckin();

            $dailyCheckin
                ->setProfile($profile)
                ->setDate(new \DateTimeImmutable());
        }

        $form = $this->createForm(DailyCheckinType::class, $dailyCheckin);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($dailyCheckin);
            $entityManager->flush();

            if (!$isEditMode) {
                return $this->redirectToRoute('app_daily_checkin_index');
            }

            return $this->redirectToRoute('app_daily_checkin_show', ['id' => $dailyCheckin->getId()]);
        }

        return $this->render('daily_checkin/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/daily-checkin', name: 'app_daily_checkin_index')]
    public function index(DailyCheckinRepository $dailyCheckinRepository): Response
    {
        $dailyCheckins = $dailyCheckinRepository->findBy([], [
            'date' => 'DESC',
        ]);
        return $this->render('daily_checkin/index.html.twig', [
            'dailyCheckins' => $dailyCheckins,
        ]);
    }

    #[Route('/daily-checkin/show/{id}', name: 'app_daily_checkin_show')]
    public function show(DailyCheckin $dailyCheckin): Response
    {
        return $this->render('daily_checkin/show.html.twig', [
            'dailyCheckin' => $dailyCheckin,
        ]);
    }
}
