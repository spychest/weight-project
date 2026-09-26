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
        yield 'milestone history' => ['/milestones'];
        yield 'milestone details' => ['/milestone/1'];
        yield 'milestone editing' => ['/milestone/1/edit'];
        yield 'account settings' => ['/account'];
        yield 'recipe catalogue' => ['/recipes'];
        yield 'recipe management' => ['/recipes/mine'];
        yield 'recipe creation' => ['/recipes/new'];
        yield 'ingredient suggestions' => ['/ingredients/suggestions?q=tom'];
        yield 'recipe details' => ['/recipes/1'];
        yield 'recipe PDF' => ['/recipes/1/pdf'];
        yield 'shopping list' => ['/shopping-list'];
        yield 'shopping list PDF' => ['/shopping-list/pdf'];
        yield 'profile editing' => ['/profile/edit'];
        yield 'favorite meal management' => ['/food/favorites'];
        yield 'favorite meal creation' => ['/food/favorites/new'];
        yield 'administration' => ['/admin'];
        yield 'administration API' => ['/admin/api/ingredients'];
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
    public function shoppingListCancellationRequiresAuthentication(): void
    {
        $browser = self::createClient();

        $browser->request('POST', '/shopping-list/cancel');

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
        self::assertSelectorExists('input[type="password"][data-password-input]');
        self::assertSelectorExists('button[data-password-toggle][aria-pressed="false"]');
    }

    #[Test]
    public function googleCallbackUsesTheOriginalHttpsUrlBehindATrustedProxy(): void
    {
        $expectedGoogleRedirectUri = 'https://weight.spychest.fr/connect/google/check';
        $_ENV['GOOGLE_REDIRECT_URI'] = $expectedGoogleRedirectUri;
        $_SERVER['GOOGLE_REDIRECT_URI'] = $expectedGoogleRedirectUri;
        putenv('GOOGLE_REDIRECT_URI='.$expectedGoogleRedirectUri);

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
