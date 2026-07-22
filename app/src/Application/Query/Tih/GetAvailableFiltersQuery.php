<?php

namespace App\Application\Query\Tih;
use App\Application\Query\Query;

class GetAvailableFiltersQuery extends Query
{
    /**
     * @param array<mixed> $currentFilters
     */
    public function __construct(
        private array $currentFilters = []
    ) {}

    /**
     * @return array<mixed>
     */
    public function getCurrentFilters(): array
    {
        return $this->currentFilters;
    }
}
