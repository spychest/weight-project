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

        $browser->request('GET', '/login');
        self::assertSelectorExists('.public-page-navigation');
    }
}
