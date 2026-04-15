<?php

namespace App\Application\Query\Tih;

class GetAvailableFiltersQuery
{
    public function __construct(
        private array $currentFilters = []
    ) {}

    public function getCurrentFilters(): array
    {
        return $this->currentFilters;
    }
}
