<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class SuspendedUserRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['disconnectSuspendedUser', 0]];
    }

    public function disconnectSuspendedUser(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isSuspended()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->invalidate();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add(
                    'error',
                    'Ce compte est suspendu. Contacte l’administration si tu penses qu’il s’agit d’une erreur.',
                );
            }
        }
        $this->tokenStorage->setToken(null);
        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
    }
}
