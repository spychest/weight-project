<?php

namespace App\Tests\Unit\Pagination;

use App\Pagination\PaginatedResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaginatedResultTest extends TestCase
{
    #[Test]
    public function itUsesFifteenItemsPerPageByDefault(): void
    {
        self::assertSame(15, PaginatedResult::DEFAULT_ITEMS_PER_PAGE);
    }

    #[Test]
    public function itExposesAvailablePreviousAndNextPages(): void
    {
        $firstPage = new PaginatedResult([], 1, 15, 31, 3);
        $middlePage = new PaginatedResult([], 2, 15, 31, 3);
        $lastPage = new PaginatedResult([], 3, 15, 31, 3);

        self::assertFalse($firstPage->hasPreviousPage());
        self::assertTrue($firstPage->hasNextPage());
        self::assertTrue($middlePage->hasPreviousPage());
        self::assertTrue($middlePage->hasNextPage());
        self::assertTrue($lastPage->hasPreviousPage());
        self::assertFalse($lastPage->hasNextPage());
    }
}
