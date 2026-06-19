<?php

namespace App\Application\Query\Tih;
use App\Application\Query\Query;

class GetAvailableFiltersQuery extends Query
{
    public function __construct(
        private array $currentFilters = []
    ) {}

    public function getCurrentFilters(): array
    {
        return $this->currentFilters;
    }
}
