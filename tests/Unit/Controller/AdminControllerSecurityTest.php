<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AdminController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminControllerSecurityTest extends TestCase
{
    #[Test]
    public function everyAdminEndpointRequiresTheAdministratorRole(): void
    {
        $controllerReflection = new \ReflectionClass(AdminController::class);
        $securityAttributes = $controllerReflection->getAttributes(IsGranted::class);

        self::assertCount(1, $securityAttributes);
        self::assertSame('ROLE_ADMIN', $securityAttributes[0]->newInstance()->attribute);
    }
}
