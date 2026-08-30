<?php

namespace App\Tests\Functional;

use App\Kernel;
use App\Repository\ProfileRepository;
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
    }

    #[Test]
    #[DataProvider('routesRequiringAProfile')]
    public function itRedirectsToProfileCreationWhenNoProfileExists(string $route): void
    {
        $browser = self::createClient();
        $profileRepository = $this->createStub(ProfileRepository::class);
        $profileRepository->method('findFirstProfile')->willReturn(null);
        self::getContainer()->set(ProfileRepository::class, $profileRepository);

        $browser->request('GET', $route);

        self::assertResponseRedirects('/profile/new');
    }

    #[Test]
    public function profileCreationPageIsAvailableWithoutAnExistingProfile(): void
    {
        $browser = self::createClient();

        $browser->request('GET', '/profile/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }
}
