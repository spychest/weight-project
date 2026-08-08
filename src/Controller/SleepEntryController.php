<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Form\SleepEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SleepEntryController extends AbstractController
{
    #[Route('/sleep/new', name: 'app_sleep_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $entityManager
            ->getRepository(Profile::class)
            ->findOneBy([]);

        $sleepEntry = new SleepEntry();

        $sleepEntry
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable());

        $form = $this->createForm(SleepEntryType::class, $sleepEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sleepEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('sleep_entry/new.html.twig', [
            'form' => $form,
        ]);
    }
}