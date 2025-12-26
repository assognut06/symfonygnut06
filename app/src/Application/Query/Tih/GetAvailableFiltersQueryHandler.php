<?php

namespace App\Application\Query\Tih;

use App\Application\DTO\Tih\AvailableFiltersDTO;
use App\Repository\TihRepository;

class GetAvailableFiltersQueryHandler
{
    public function __construct(
        private TihRepository $tihRepository
    ) {}

    public function __invoke(GetAvailableFiltersQuery $query): AvailableFiltersDTO
    {
        return $this->tihRepository->getAvailableFilters(
            $query->getCurrentFilters()
        );
    }
}
