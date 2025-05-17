<?php

// src/Service/PaginationService.php

namespace App\Service;

use App\Repository\AssoRecommanderRepository;
use Doctrine\ORM\EntityManagerInterface;

class PaginationService
{
    private $entityManager;
    private $limit;
    private $assoRecommanderRepository;

    public function __construct(EntityManagerInterface $entityManager, int $limit, AssoRecommanderRepository $assoRecommanderRepository)
    {
        $this->entityManager = $entityManager;
        $this->limit = $limit;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getPaginatedData(string $entityClass, int $page): array
    {
        $start = $this->limit * ($page - 1);
        $repository = $this->entityManager->getRepository($entityClass);
        $data = $repository->findBy([], [], $this->limit, $start);
        $total = count($repository->findAll());
        $pages = ceil($total / $this->limit);
        $cities = [];

        if ($entityClass === \App\Entity\AssoRecommander::class) {
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

    public function getPaginatedDataCity(string $entityClass, int $page, array $city): array
    {
        $start = $this->limit * ($page - 1);
        $repository = $this->entityManager->getRepository($entityClass);
        $data = $repository->findBy($city, [], $this->limit, $start);
        $total = count($repository->findBy($city, []));
        $pages = ceil($total / $this->limit);
        $cities = [];

        if ($entityClass === \App\Entity\AssoRecommander::class) {
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

    public function getPaginatedDataSearch(string $entityClass, string $search): array
    {
        $repository = $this->entityManager->getRepository($entityClass);
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