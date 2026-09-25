<?php

namespace App\Tests\Functional;

use App\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

final class LoginTemplateTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function loginPageOffersOptionalPersistentLoginForPasswordAndGoogle(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $twig = $container->get(Environment::class);
        $requestStack = $container->get(RequestStack::class);

        self::assertInstanceOf(Environment::class, $twig);
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $request = Request::create('/login');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        $renderedLoginPage = $twig->render('security/login.html.twig', [
            'last_username' => '',
            'authentication_error' => null,
        ]);

        self::assertStringContainsString('name="_remember_me"', $renderedLoginPage);
        self::assertStringNotContainsString('name="_remember_me" value="1" checked', $renderedLoginPage);
        self::assertStringContainsString('Rester connecté', $renderedLoginPage);
        self::assertStringContainsString('data-google-connect', $renderedLoginPage);
        self::assertStringContainsString('remember-me.js', $renderedLoginPage);
    }
}
