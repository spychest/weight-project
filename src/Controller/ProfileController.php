<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile/new', name: 'app_profile_new')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) { throw $this->createAccessDeniedException(); }
        if ($authenticatedUser->getProfile() !== null) { return $this->redirectToRoute('app_dashboard'); }
        $profile = new Profile();
        $profile->setUser($authenticatedUser);

        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($profile);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('profile/new.html.twig', [
            'form' => $form,
        ]);
    }
}
