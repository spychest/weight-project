<?php

namespace App\Tests\Unit\DTO;

use App\DTO\Admin\AdminUserData;
use App\Entity\Profile;
use App\Entity\Recipe;
use App\Entity\User;
use App\Entity\UserIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminUserDataTest extends TestCase
{
    #[Test]
    public function itOnlyExposesNonSensitiveAdministrativeInformation(): void
    {
        $user = (new User())
            ->setEmail('private@example.test')
            ->setPassword('secret-hash')
            ->addIdentity(new UserIdentity('google', 'private-google-id', 'private@example.test'));
        $profile = (new Profile())->setDisplayName('Pseudo public');
        $profile->addRecipe((new Recipe())->setTitle('Recette publique'));
        $user->setProfile($profile);

        $identifierProperty = new \ReflectionProperty(User::class, 'id');
        $identifierProperty->setValue($user, 42);
        $serializedUser = AdminUserData::fromUser($user)->jsonSerialize();
        $serializedJson = json_encode($serializedUser, JSON_THROW_ON_ERROR);

        self::assertSame('Pseudo public', $serializedUser['displayName']);
        self::assertSame(1, $serializedUser['publicRecipeCount']);
        self::assertSame(['Mot de passe', 'Google'], $serializedUser['authenticationMethods']);
        self::assertSame([
            'id',
            'displayName',
            'avatarUrl',
            'roles',
            'hasProfile',
            'createdAt',
            'lastLoginAt',
            'suspended',
            'suspendedAt',
            'suspensionReason',
            'authenticationMethods',
            'publicRecipeCount',
        ], array_keys($serializedUser));
        self::assertStringNotContainsString('private@example.test', $serializedJson);
        self::assertStringNotContainsString('private-google-id', $serializedJson);
        foreach (['email', 'weight', 'meal', 'mood', 'hydration', 'sleep', 'activity', 'shoppingList'] as $forbiddenKey) {
            self::assertArrayNotHasKey($forbiddenKey, $serializedUser);
        }
    }
}
