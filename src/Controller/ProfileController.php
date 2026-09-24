<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\ProfileType;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/new', name: 'app_profile_new')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $authenticatedUser = $this->getUser();
        if (!$authenticatedUser instanceof User) {
            throw $this->createAccessDeniedException();
        }
        if ($authenticatedUser->getProfile() !== null) {
            return $this->redirectToRoute('app_dashboard');
        }

        $profile = new Profile();
        $profile->setUser($authenticatedUser);
        foreach ($authenticatedUser->getIdentities() as $identity) {
            if ($identity->getProvider() === 'google' && $identity->getProviderAvatarUrl() !== null) {
                $profile->setGoogleAvatarUrl($identity->getProviderAvatarUrl());
                break;
            }
        }

        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveUploadedAvatar($profile, $form->get('avatar')->getData(), $slugger);
            $entityManager->persist($profile);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('profile/new.html.twig', [
            'form' => $form,
            'editMode' => false,
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeCustomAvatarWhenRequested(
                $profile,
                (bool) $form->get('removeAvatar')->getData(),
            );
            $this->saveUploadedAvatar($profile, $form->get('avatar')->getData(), $slugger);
            $entityManager->flush();
            $this->addFlash('success', 'Ton profil a été mis à jour.');

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('profile/new.html.twig', [
            'form' => $form,
            'editMode' => true,
            'profile' => $profile,
        ]);
    }

    private function saveUploadedAvatar(
        Profile $profile,
        ?UploadedFile $uploadedAvatar,
        SluggerInterface $slugger,
    ): void {
        if (!$uploadedAvatar instanceof UploadedFile) {
            return;
        }

        $avatarDirectory = $this->getAvatarDirectory();
        $filesystem = new Filesystem();
        $filesystem->mkdir($avatarDirectory);
        $previousAvatarFilename = $profile->getAvatarFilename();
        $safeDisplayName = strtolower($slugger->slug($profile->getDisplayName())->toString()) ?: 'profil';
        $newAvatarFilename = sprintf(
            '%s-%s.%s',
            $safeDisplayName,
            bin2hex(random_bytes(6)),
            $uploadedAvatar->guessExtension() ?: 'jpg',
        );
        $uploadedAvatar->move($avatarDirectory, $newAvatarFilename);
        $profile->setAvatarFilename($newAvatarFilename);

        if ($previousAvatarFilename !== null) {
            $filesystem->remove($avatarDirectory.DIRECTORY_SEPARATOR.$previousAvatarFilename);
        }
    }

    private function removeCustomAvatarWhenRequested(Profile $profile, bool $removeAvatar): void
    {
        if (!$removeAvatar || $profile->getAvatarFilename() === null) {
            return;
        }

        (new Filesystem())->remove(
            $this->getAvatarDirectory().DIRECTORY_SEPARATOR.$profile->getAvatarFilename(),
        );
        $profile->setAvatarFilename(null);
    }

    private function getAvatarDirectory(): string
    {
        return (string) $this->getParameter('kernel.project_dir').'/public/uploads/profiles';
    }
}
