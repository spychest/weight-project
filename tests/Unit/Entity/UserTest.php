<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Profile;
use App\Entity\User;
use App\Entity\UserIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function itNormalizesEmailAndAlwaysHasTheUserRole(): void
    {
        $user = (new User())->setEmail('  PERSONNE@Example.COM ');
        self::assertSame('personne@example.com', $user->getUserIdentifier());
        self::assertContains('ROLE_USER', $user->getRoles());
    }

    #[Test]
    public function itSynchronizesProfileAndExternalIdentityRelationships(): void
    {
        $user = new User();
        $profile = new Profile();
        $identity = new UserIdentity('google', 'google-subject');
        $user->setProfile($profile)->addIdentity($identity);
        self::assertSame($user, $profile->getUser());
        self::assertSame($user, $identity->getUser());
        self::assertCount(1, $user->getIdentities());
    }
}
