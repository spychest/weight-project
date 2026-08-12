<?php

namespace App\Controller;

use App\Entity\DrinkEntry;
use App\Entity\Profile;
use App\Form\DrinkEntryType;
use App\Repository\DrinkEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DrinkEntryController extends AbstractController
{
    #[Route('/drink/new', name: 'app_drink_new')]
    #[Route('/drink/new/{id}', name: 'app_drink_edit')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ?DrinkEntry $drinkEntry = null
    ): Response {
        $editMod = true;
        if($drinkEntry === null){
            $editMod = false;
            $profile = $entityManager
                ->getRepository(Profile::class)
                ->findOneBy([]);

            $drinkEntry = new DrinkEntry();

            $drinkEntry
                ->setProfile($profile)
                ->setDate(new \DateTimeImmutable());
        }


        $form = $this->createForm(DrinkEntryType::class, $drinkEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($drinkEntry);
            $entityManager->flush();
            if($editMod !== true) {
                return $this->redirectToRoute('app_dashboard');
            }
            return $this->redirectToRoute('app_drink_index');
        }

        return $this->render('drink_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/drink/show/{id}', name: 'app_drink_show')]
    public function show(DrinkEntry $drinkEntry): Response
    {
        return $this->render('drink_entry/show.html.twig', [
            'drinkEntry' => $drinkEntry,
        ]);
    }

    #[Route('/drink/index', name: 'app_drink_index')]
    public function index(DrinkEntryRepository $drinkEntryRepository): Response
    {
        $drinkEntries = $drinkEntryRepository->findBy([], []);

        return $this->render('drink_entry/index.html.twig', [
            'controller_name' => 'DrinkEntryController',
            'drinkEntries' => $drinkEntries,
        ]);
    }
}