<?php

namespace App\Application\Query\Tih;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class SearchTihQueryHandler
{
    public function __construct(
        private TihRepository $tihRepository
    ) {}
    /**
     * @return Paginator<Tih>
     */
    public function __invoke(SearchTihQuery $query): Paginator
    {
        return $this->tihRepository->searchWithFilters(
            $query->getFilters(),
            $query->getPage(),
            $query->getLimit()
        );
    }
}
