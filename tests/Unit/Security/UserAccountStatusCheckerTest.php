<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserAccountStatusChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserAccountStatusCheckerTest extends TestCase
{
    #[Test]
    public function itAcceptsAnActiveUser(): void
    {
        (new UserAccountStatusChecker())->checkPreAuth(new User());

        self::addToAssertionCount(1);
    }

    #[Test]
    public function itRejectsASuspendedUser(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Ce compte est suspendu.');

        (new UserAccountStatusChecker())->checkPreAuth((new User())->suspend());
    }
}
