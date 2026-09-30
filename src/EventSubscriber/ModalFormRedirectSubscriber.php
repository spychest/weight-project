<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ModalFormRedirectSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'replaceModalRedirect'];
    }

    public function replaceModalRedirect(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$event->isMainRequest()
            || !$request->headers->has('X-Modal-Form')
            || !$response instanceof RedirectResponse
        ) {
            return;
        }

        $event->setResponse(new Response('', Response::HTTP_NO_CONTENT, [
            'X-Modal-Redirect' => $response->getTargetUrl(),
        ]));
    }
}
