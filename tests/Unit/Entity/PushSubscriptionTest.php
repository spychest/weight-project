<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PushSubscription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PushSubscriptionTest extends TestCase
{
    #[Test]
    public function itHashesTheEndpointUsedForDatabaseLookup(): void
    {
        $endpoint = 'https://push.example.test/subscription/123';
        $pushSubscription = (new PushSubscription())->setEndpoint($endpoint);

        self::assertSame($endpoint, $pushSubscription->getEndpoint());
        self::assertSame(hash('sha256', $endpoint), $pushSubscription->getEndpointHash());
    }
}
