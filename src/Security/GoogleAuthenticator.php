<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\UserIdentity;
use App\Repository\UserIdentityRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(private readonly ClientRegistry $clientRegistry, private readonly UserRepository $userRepository, private readonly UserIdentityRepository $identityRepository, private readonly EntityManagerInterface $entityManager, private readonly UrlGeneratorInterface $urlGenerator) {}
    public function supports(Request $request): ?bool { return $request->attributes->get('_route') === 'app_google_check'; }
    public function authenticate(Request $request): Passport
    {
        $googleClient = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($googleClient);
        return new SelfValidatingPassport(new UserBadge('google_'.$accessToken->getToken(), function () use ($googleClient, $accessToken, $request): User {
            $googleUser = $googleClient->fetchUserFromToken($accessToken);
            if (!$googleUser instanceof GoogleUser || $googleUser->getEmail() === null) { throw new CustomUserMessageAuthenticationException('Google n’a pas fourni une adresse e-mail utilisable.'); }
            $googleUserId = (string) $googleUser->getId();
            $userIdToLink = $request->getSession()->remove('google_identity_link_user_id');
            $identity = $this->identityRepository->findGoogleIdentity($googleUserId);
            if ($identity !== null) {
                if (is_int($userIdToLink) && $identity->getUser()->getId() !== $userIdToLink) {
                    throw new CustomUserMessageAuthenticationException('Ce compte Google est déjà associé à un autre compte.');
                }
                return $identity->getUser();
            }
            if (is_int($userIdToLink)) {
                $userToLink = $this->userRepository->find($userIdToLink);
                if ($userToLink instanceof User) {
                    $userToLink->addIdentity(new UserIdentity('google', $googleUserId, $googleUser->getEmail()));
                    $this->entityManager->flush();
                    return $userToLink;
                }
            }
            $existingUser = $this->userRepository->findOneByEmail($googleUser->getEmail());
            if ($existingUser !== null) { throw new CustomUserMessageAuthenticationException('Un compte existe déjà avec cet e-mail. Connecte-toi avec ton mot de passe avant d’y associer Google.'); }
            $newUser = (new User())->setEmail($googleUser->getEmail())->setEmailVerified(true);
            $newUser->addIdentity(new UserIdentity('google', $googleUserId, $googleUser->getEmail()));
            $this->entityManager->persist($newUser);
            $this->entityManager->flush();
            return $newUser;
        }));
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    { $user = $token->getUser(); return new RedirectResponse($this->urlGenerator->generate($user instanceof User && $user->getProfile() === null ? 'app_profile_new' : 'app_dashboard')); }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    { $request->getSession()->getFlashBag()->add('error', $exception->getMessageKey()); return new RedirectResponse($this->urlGenerator->generate('app_login')); }
}
