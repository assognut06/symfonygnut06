<?php
// src/Service/PaginationService.php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class PaginationService
{
    private $entityManager;
    private $limit;

    public function __construct(EntityManagerInterface $entityManager, int $limit)
    {
        $this->entityManager = $entityManager;
        $this->limit = $limit;
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

        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    }
}