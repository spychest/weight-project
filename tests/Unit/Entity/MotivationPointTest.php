<?php

namespace App\Tests\Unit\Entity;

use App\Entity\MotivationPoint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MotivationPointTest extends TestCase
{
    #[Test]
    public function itStoresTrimmedContentAndAPositivePosition(): void
    {
        $motivationPoint = (new MotivationPoint())
            ->setContent('  Prendre soin de moi  ')
            ->setPosition(0);

        self::assertSame('Prendre soin de moi', $motivationPoint->getContent());
        self::assertSame(1, $motivationPoint->getPosition());
    }
}
