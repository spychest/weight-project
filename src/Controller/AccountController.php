<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangeEmailType;
use App\Form\ChangePasswordType;
use App\Form\DeleteAccountType;
use App\Form\ImportProfileDataType;
use App\Form\ThemePreferenceType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CurrentUserProfileProvider;
use App\Service\ProfileDataBackupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, CurrentUserProfileProvider $currentUserProfileProvider, ProfileDataBackupService $profileDataBackupService): Response
    {
        $authenticatedUser = $this->getUser();

        if (!$authenticatedUser instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $googleIdentityIsLinked = $authenticatedUser->getIdentities()->exists(
            static fn (int $index, $identity): bool => $identity->getProvider() === 'google',
        );

        $accountHasPassword = $authenticatedUser->getPassword() !== null;
        $changeEmailForm = $this->createForm(ChangeEmailType::class, null, [
            'password_confirmation_required' => $accountHasPassword,
            'action' => $this->generateUrl('app_account').'#email-settings',
        ]);
        $changePasswordForm = $this->createForm(ChangePasswordType::class, null, [
            'current_password_required' => $accountHasPassword,
            'action' => $this->generateUrl('app_account').'#password-settings',
        ]);
        $deleteAccountForm = $this->createForm(DeleteAccountType::class, null, [
            'password_confirmation_required' => $accountHasPassword,
            'action' => $this->generateUrl('app_account').'#danger-zone',
        ]);
        $importProfileDataForm = $this->createForm(ImportProfileDataType::class, null, [
            'action' => $this->generateUrl('app_account').'#profile-data',
        ]);
        $themePreferenceForm = $this->createForm(ThemePreferenceType::class, [
            'darkModeEnabled' => $authenticatedUser->isDarkModeEnabled(),
        ], [
            'action' => $this->generateUrl('app_account').'#appearance-settings',
        ]);

        $changeEmailForm->handleRequest($request);
        $changePasswordForm->handleRequest($request);
        $deleteAccountForm->handleRequest($request);
        $importProfileDataForm->handleRequest($request);
        $themePreferenceForm->handleRequest($request);

        if ($themePreferenceForm->isSubmitted() && $themePreferenceForm->isValid()) {
            $themePreferenceData = $themePreferenceForm->getData();
            $authenticatedUser->setDarkModeEnabled((bool) ($themePreferenceData['darkModeEnabled'] ?? false));
            $entityManager->flush();
            $this->addFlash('success', 'Ton apparence a été mise à jour.');
            return $this->redirectToRoute('app_account');
        }

        if ($changeEmailForm->isSubmitted() && $changeEmailForm->isValid()) {
            $currentPassword = (string) $changeEmailForm->get('currentPassword')->getData();
            $newEmail = (string) $changeEmailForm->get('newEmail')->getData();
            if ($accountHasPassword && !$passwordHasher->isPasswordValid($authenticatedUser, $currentPassword)) {
                $changeEmailForm->get('currentPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            } elseif (($existingUser = $userRepository->findOneByEmail($newEmail)) !== null && $existingUser !== $authenticatedUser) {
                $changeEmailForm->get('newEmail')->addError(new FormError('Cette adresse e-mail est déjà utilisée.'));
            } else {
                $authenticatedUser->setEmail($newEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Ton adresse e-mail a été mise à jour.');
                return $this->redirectToRoute('app_account');
            }
        }

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $currentPassword = (string) $changePasswordForm->get('currentPassword')->getData();
            if ($accountHasPassword && !$passwordHasher->isPasswordValid($authenticatedUser, $currentPassword)) {
                $changePasswordForm->get('currentPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            } else {
                $newPassword = (string) $changePasswordForm->get('newPassword')->getData();
                $authenticatedUser->setPassword($passwordHasher->hashPassword($authenticatedUser, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Ton mot de passe a été mis à jour.');
                return $this->redirectToRoute('app_account');
            }
        }

        if ($deleteAccountForm->isSubmitted() && $deleteAccountForm->isValid()) {
            $currentPassword = (string) $deleteAccountForm->get('currentPassword')->getData();
            if ($accountHasPassword && !$passwordHasher->isPasswordValid($authenticatedUser, $currentPassword)) {
                $deleteAccountForm->get('currentPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            } else {
                $entityManager->remove($authenticatedUser);
                $entityManager->flush();
                $tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                return $this->redirectToRoute('app_home');
            }
        }

        if ($importProfileDataForm->isSubmitted() && $importProfileDataForm->isValid()) {
            $uploadedBackupFile = $importProfileDataForm->get('backupFile')->getData();
            if ($uploadedBackupFile instanceof UploadedFile) {
                try {
                    $importedEntryCount = $profileDataBackupService->importProfile($currentUserProfileProvider->getRequiredProfile(), $uploadedBackupFile->getContent());
                    $this->addFlash('success', sprintf('Sauvegarde importée : %d entrée(s) restaurée(s).', $importedEntryCount));
                    return $this->redirectToRoute('app_account');
                } catch (\InvalidArgumentException $exception) {
                    $importProfileDataForm->get('backupFile')->addError(new FormError($exception->getMessage()));
                }
            }
        }

        return $this->render('account/index.html.twig', [
            'account' => $authenticatedUser,
            'googleIdentityIsLinked' => $googleIdentityIsLinked,
            'changeEmailForm' => $changeEmailForm,
            'changePasswordForm' => $changePasswordForm,
            'deleteAccountForm' => $deleteAccountForm,
            'importProfileDataForm' => $importProfileDataForm,
            'themePreferenceForm' => $themePreferenceForm,
        ]);
    }

    #[Route('/account/data/export', name: 'app_account_data_export', methods: ['GET'])]
    public function exportData(CurrentUserProfileProvider $currentUserProfileProvider, ProfileDataBackupService $profileDataBackupService): JsonResponse
    {
        $response = new JsonResponse($profileDataBackupService->exportProfile($currentUserProfileProvider->getRequiredProfile()));
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, sprintf('weight-project-backup-%s.json', (new \DateTimeImmutable())->format('Y-m-d'))));
        return $response;
    }
}
