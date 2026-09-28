<?php

namespace App\DTO;

final readonly class MotivationPointData
{
    public function __construct(
        public int $id,
        public string $content,
        public int $position,
    ) {
    }
}
