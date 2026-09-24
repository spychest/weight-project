<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\EventListener\UpdateLastLoginAtListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class UpdateLastLoginAtListenerTest extends TestCase
{
    #[Test]
    public function itStoresTheDateOfEverySuccessfulApplicationLogin(): void
    {
        $user = new User();
        $loginSuccessEvent = $this->createStub(LoginSuccessEvent::class);
        $loginSuccessEvent->method('getUser')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $listener = new UpdateLastLoginAtListener($entityManager);
        $listener($loginSuccessEvent);

        self::assertInstanceOf(\DateTimeImmutable::class, $user->getLastLoginAt());
    }
}
