<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Form\SleepEntryType;
use App\Repository\SleepEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SleepEntryController extends AbstractController
{
    #[Route('/sleep/new', name: 'app_sleep_new')]
    #[Route('/sleep/new/{id}', name: 'app_sleep_edit')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ?SleepEntry $sleepEntry = null
    ): Response {
        $editMod = true;
        if($sleepEntry == null){
            $editMod = false;

            $profile = $entityManager
                ->getRepository(Profile::class)
                ->findOneBy([]);

            $sleepEntry = new SleepEntry();

            $sleepEntry
                ->setProfile($profile)
                ->setDate(new \DateTimeImmutable());
        }


        $form = $this->createForm(SleepEntryType::class, $sleepEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sleepEntry);
            $entityManager->flush();
            if($editMod !== true) {
                return $this->redirectToRoute('app_dashboard');
            }
            return $this->redirectToRoute('app_sleep_show', ['id' => $sleepEntry->getId()]);
        }

        return $this->render('sleep_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/sleep', name: 'app_sleep_index')]
    public function index(SleepEntryRepository $sleepEntryRepository): Response
    {
        $sleepEntries = $sleepEntryRepository->findBy([], []);
        return $this->render('sleep_entry/index.html.twig', [
            'controller_name' => 'SleepEntryController',
            'sleepEntries' => $sleepEntries,
        ]);
    }

    #[Route('/sleep/show/{id}', name: 'app_sleep_show')]
    public function show(SleepEntry $sleepEntry): Response
    {
        return $this->render('sleep_entry/show.html.twig', [
            'sleepEntry' => $sleepEntry,
        ]);
    }
}