<?php

namespace App\Application\Query\Tih;
use App\Application\Query\Query;

class SearchTihQuery extends Query
{

    /**
     * @param array<mixed> $filters
     */
    public function __construct(
        private array $filters = [],
        private int $page = 1,
        private int $limit = 12
    ) {}

    /**
     * @return array<mixed>
     */
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
