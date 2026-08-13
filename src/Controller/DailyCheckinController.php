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
    //app_daily_checkin_edit
    #[Route('/daily-checkin/new', name: 'app_daily_checkin_new')]
    #[Route('/daily-checkin/new/{id}', name: 'app_daily_checkin_edit')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        DailyCheckin $dailyCheckin = null
    ): Response {
        $editMod = true;
        if($dailyCheckin == null){
            $editMod = false;
            $profile = $entityManager
                ->getRepository(Profile::class)
                ->findOneBy([]);

            $checkin = new DailyCheckin();

            $checkin
                ->setProfile($profile)
                ->setDate(new \DateTimeImmutable());
        }


        $form = $this->createForm(DailyCheckinType::class, $checkin);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($checkin);
            $entityManager->flush();
            if($editMod !== true) {
                return $this->redirectToRoute('app_daily_checkin_index');
            }
            return $this->redirectToRoute('app_daily_checkin_show', ['id' => $checkin->getId()] );
        }

        return $this->render('daily_checkin/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/daily-checkin', name: 'app_daily_checkin_index')]
    public function index(DailyCheckinRepository $dailyCheckinRepository): Response
    {
        $dailyCheckins = $dailyCheckinRepository->findBy([], []);
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