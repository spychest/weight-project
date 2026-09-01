<?php

namespace App\Controller;

use App\Entity\DailyCheckin;
use App\Form\DailyCheckinType;
use App\Pagination\PaginatedResult;
use App\Repository\DailyCheckinRepository;
use App\Service\CurrentUserProfileProvider;
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
        CurrentUserProfileProvider $currentUserProfileProvider,
        ?DailyCheckin $dailyCheckin = null,
    ): Response {
        $isEditMode = $dailyCheckin !== null;
        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($dailyCheckin?->getProfile())) { throw $this->createNotFoundException(); }

        if ($dailyCheckin === null) {
            $dailyCheckin = new DailyCheckin();

            $dailyCheckin
                ->setProfile($currentUserProfileProvider->getRequiredProfile())
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
    public function index(Request $request, DailyCheckinRepository $dailyCheckinRepository, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        $pagination = $dailyCheckinRepository->paginateAllForProfileFromNewest(
            $currentUserProfileProvider->getRequiredProfile(),
            $request->query->getInt('page', 1),
            PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
        );
        return $this->render('daily_checkin/index.html.twig', [
            'dailyCheckins' => $pagination->items,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/daily-checkin/show/{id}', name: 'app_daily_checkin_show')]
    public function show(DailyCheckin $dailyCheckin, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($dailyCheckin->getProfile())) { throw $this->createNotFoundException(); }
        return $this->render('daily_checkin/show.html.twig', [
            'dailyCheckin' => $dailyCheckin,
        ]);
    }
}
