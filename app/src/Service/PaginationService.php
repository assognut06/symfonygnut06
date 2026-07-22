<?php

// src/Service/PaginationService.php

namespace App\Service;

use App\Repository\AssoRecommanderRepository;
use Doctrine\ORM\EntityManagerInterface;

class PaginationService
{
    private int $limit;
    private AssoRecommanderRepository $assoRecommanderRepository;

    public function __construct(int $limit, AssoRecommanderRepository $assoRecommanderRepository)
    {
        $this->limit = $limit;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getPaginatedData(string $entityClass, int $page): mixed
    {
        $start = $this->limit * ($page - 1);
        $cities = [];
        $data = [];
        $total = 0;
        $pages = 0;
        $cities = [];

        if (\App\Entity\AssoRecommander::class === $entityClass) {
            $cities = $this->assoRecommanderRepository->findDistinctCities();
        }

        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'cities' => $cities,
        ];
    }
/**
 * @param array<string,mixed> $city
 */
    public function getPaginatedDataCity(string $entityClass, int $page, array $city): mixed
    {
        $start = $this->limit * ($page - 1);
        $cities = [];
        $data = [];
        $total = 0;
        $pages = 0;

        if ($entityClass === \App\Entity\AssoRecommander::class) {
            $cities = $this->assoRecommanderRepository->findDistinctCities();
            $data = $this->assoRecommanderRepository->findBy($city, [], $this->limit, $start);
            $total = count($this->assoRecommanderRepository->findBy($city, []));
            $pages = ceil($total / $this->limit);
        }

        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'cities' => $cities,
        ];
    }

    public function getPaginatedDataSearch(string $entityClass, string $search): mixed
    {
        $data = [];
        $total = 0;

        if ($entityClass === \App\Entity\AssoRecommander::class) {
            $data = $this->assoRecommanderRepository->findSearch($search);
            $total = count($data);
        }

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}