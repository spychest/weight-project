<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ModalFormRedirectSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ModalFormRedirectSubscriberTest extends TestCase
{
    public function testModalRedirectIsReturnedWithoutFollowingTheDestination(): void
    {
        $request = Request::create('/food/new', 'POST');
        $request->headers->set('X-Modal-Form', '1');
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RedirectResponse('/dashboard'),
        );

        (new ModalFormRedirectSubscriber())->replaceModalRedirect($event);

        self::assertSame(204, $event->getResponse()->getStatusCode());
        self::assertSame('/dashboard', $event->getResponse()->headers->get('X-Modal-Redirect'));
    }

    public function testRegularRedirectIsNotChanged(): void
    {
        $redirectResponse = new RedirectResponse('/dashboard');
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/food/new', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            $redirectResponse,
        );

        (new ModalFormRedirectSubscriber())->replaceModalRedirect($event);

        self::assertSame($redirectResponse, $event->getResponse());
    }
}
