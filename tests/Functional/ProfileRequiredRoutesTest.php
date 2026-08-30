<?php

namespace App\Tests\Functional;

use App\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfileRequiredRoutesTest extends WebTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function routesRequiringAProfile(): iterable
    {
        yield 'dashboard' => ['/dashboard'];
        yield 'graphs' => ['/graph'];
        yield 'activity creation' => ['/activity/new'];
        yield 'milestone creation' => ['/milestone/new'];
        yield 'account settings' => ['/account'];
    }

    #[Test]
    #[DataProvider('routesRequiringAProfile')]
    public function itRedirectsAnonymousVisitorsToLogin(string $route): void
    {
        $browser = self::createClient();
        $browser->request('GET', $route);

        self::assertResponseRedirects('http://localhost/login');
    }

    #[Test]
    public function profileCreationPageRequiresAuthentication(): void
    {
        $browser = self::createClient();

        $browser->request('GET', '/profile/new');

        self::assertResponseRedirects('http://localhost/login');
    }

    #[Test]
    public function landingLoginAndRegistrationPagesArePublic(): void
    {
        $browser = self::createClient();
        foreach (['/', '/login', '/register'] as $publicRoute) {
            $browser->request('GET', $publicRoute);
            self::assertResponseIsSuccessful();
        }

        $browser->request('GET', '/');
        self::assertSelectorNotExists('.site-header');
        self::assertSelectorExists('meta[name="viewport"]');

        $browser->request('GET', '/login');
        self::assertSelectorExists('.public-page-navigation');
    }

    #[Test]
    public function googleCallbackUsesTheOriginalHttpsUrlBehindATrustedProxy(): void
    {
        $browser = self::createClient([], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'weight.spychest.fr',
            'HTTP_X_FORWARDED_HOST' => 'weight.spychest.fr',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ]);

        $browser->request('GET', '/connect/google');

        self::assertResponseRedirects();
        $googleAuthorizationUrl = (string) $browser->getResponse()->headers->get('Location');
        self::assertStringContainsString(
            'redirect_uri=https%3A%2F%2Fweight.spychest.fr%2Fconnect%2Fgoogle%2Fcheck',
            $googleAuthorizationUrl,
        );
    }

}
