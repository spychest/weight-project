<?php

namespace App\Controller;

use App\Entity\WeightEntry;
use App\Form\WeightEntryType;
use App\Repository\ProfileRepository;
use App\Repository\WeightEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WeightEntryController extends AbstractController
{
    #[Route('/weight/new', name: 'app_weight_new')]
    #[Route('/weight/new/{id}', name: 'app_weight_edit')]
    public function new(
        Request $request,
        ProfileRepository $profileRepository,
        EntityManagerInterface $entityManager,
        ?WeightEntry $weightEntry = null
    ): Response {
        $editMod = true;
        if($weightEntry == null){
            $editMod = false;

            $weightEntry = new WeightEntry();
        }

        $form = $this->createForm(
            WeightEntryType::class,
            $weightEntry
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($editMod === false){
                $profile = $profileRepository->findOneBy([]);

                if ($profile === null) {
                    throw new \LogicException('No profile configured.');
                }
                $weightEntry->setProfile($profile);
            }

            $entityManager->persist($weightEntry);
            $entityManager->flush();

            if($editMod !== true) {
                return $this->redirectToRoute('app_dashboard');
            }
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('weight_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/weight', name: 'app_weight_index')]
    public function index(WeightEntryRepository $weightEntryRepository): Response
    {
        $weightEntries = $weightEntryRepository->findBy([], []);
        return $this->render('weight_entry/index.html.twig', [
            'controller_name' => 'WeightEntryController',
            'weightEntries' => $weightEntries,
        ]);
    }

    #[Route('/weight/show/{id}', name: 'app_weight_show')]
    public function show(WeightEntry $weightEntry):Response{
        return $this->render('weight_entry/show.html.twig', [
            'weightEntry' => $weightEntry,
        ]);
    }
}
