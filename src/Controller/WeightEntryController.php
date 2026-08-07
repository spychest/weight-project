<?php

namespace App\Controller;

use App\Entity\WeightEntry;
use App\Form\WeightEntryType;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WeightEntryController extends AbstractController
{
    #[Route('/weight/new', name: 'app_weight_new')]
    public function new(
        Request $request,
        ProfileRepository $profileRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $profileRepository->findOneBy([]);

        if ($profile === null) {
            throw new \LogicException('No profile configured.');
        }

        $weightEntry = new WeightEntry();

        $form = $this->createForm(
            WeightEntryType::class,
            $weightEntry
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $weightEntry->setProfile($profile);

            $entityManager->persist($weightEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('weight_entry/new.html.twig', [
            'form' => $form,
        ]);
    }
}
