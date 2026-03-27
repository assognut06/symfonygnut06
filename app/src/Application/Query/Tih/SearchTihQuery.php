<?php

namespace App\Application\Query\Tih;

class SearchTihQuery
{
    public function __construct(
        private array $filters = [],
        private int $page = 1,
        private int $limit = 12
    ) {}

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
