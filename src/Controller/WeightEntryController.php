<?php

namespace App\Controller;

use App\Entity\WeightEntry;
use App\Form\WeightEntryType;
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
        EntityManagerInterface $entityManager,
    ): Response
    {
        $weightEntry = new WeightEntry();

        $form = $this->createForm(
            WeightEntryType::class,
            $weightEntry
        );

        return $this->render('weight_entry/index.html.twig', [
            'controller_name' => 'WeightEntryController',
        ]);
    }
}
