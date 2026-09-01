<?php

namespace App\Pagination;

final readonly class PaginatedResult
{
    public const DEFAULT_ITEMS_PER_PAGE = 15;

    /**
     * @param list<object> $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $itemsPerPage,
        public int $totalItems,
        public int $totalPages,
    ) {
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }
}
