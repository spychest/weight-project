<?php

namespace App\Controller;

use App\Entity\SleepEntry;
use App\Form\SleepEntryType;
use App\Pagination\PaginatedResult;
use App\Repository\SleepEntryRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SleepEntryController extends AbstractController
{
    #[Route('/sleep/new', name: 'app_sleep_new')]
    #[Route('/sleep/new/{id}', name: 'app_sleep_edit')]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
        ?SleepEntry $sleepEntry = null,
    ): Response {
        $isEditMode = $sleepEntry !== null;
        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($sleepEntry?->getProfile())) { throw $this->createNotFoundException(); }

        if ($sleepEntry === null) {

            $sleepEntry = new SleepEntry();

            $sleepEntry
                ->setProfile($currentUserProfileProvider->getRequiredProfile())
                ->setDate(new \DateTimeImmutable());
        }

        $form = $this->createForm(SleepEntryType::class, $sleepEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sleepEntry);
            $entityManager->flush();
            if (!$isEditMode) {
                return $this->redirectToRoute('app_dashboard');
            }

            return $this->redirectToRoute('app_sleep_show', ['id' => $sleepEntry->getId()]);
        }

        return $this->render('sleep_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/sleep', name: 'app_sleep_index')]
    public function index(Request $request, SleepEntryRepository $sleepEntryRepository, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        $pagination = $sleepEntryRepository->paginateAllForProfileFromNewest(
            $currentUserProfileProvider->getRequiredProfile(),
            $request->query->getInt('page', 1),
            PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
        );
        return $this->render('sleep_entry/index.html.twig', [
            'controller_name' => 'SleepEntryController',
            'sleepEntries' => $pagination->items,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/sleep/show/{id}', name: 'app_sleep_show')]
    public function show(SleepEntry $sleepEntry, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($sleepEntry->getProfile())) { throw $this->createNotFoundException(); }
        return $this->render('sleep_entry/show.html.twig', [
            'sleepEntry' => $sleepEntry,
        ]);
    }
}
