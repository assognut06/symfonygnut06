<?php

namespace App\Repository;

use App\Entity\AssoRecommander;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssoRecommander>
 */
class AssoRecommanderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssoRecommander::class);
    }

    // src/Repository/AssoRecommanderRepository.php

    public function existsByOrganizationSlug(string $organizationSlug): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.organizationSlug = :organizationSlug')
            ->setParameter('organizationSlug', $organizationSlug)
            ->getQuery();

        return $qb->getSingleScalarResult() > 0;
    }

    public function findDistinctCities(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT DISTINCT a.city
            FROM App\Entity\AssoRecommander a
            ORDER BY a.city ASC'
        );

        return $query->getResult();
    }

    public function findSearch(string $search): array
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT a
            FROM App\Entity\AssoRecommander a
            WHERE a.name LIKE :search
            ORDER BY a.name ASC'
        )->setParameter('search', '%' . $search . '%');
        return $query->getResult();
    }
    
    //    /**
    //     * @return AssoRecommander[] Returns an array of AssoRecommander objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AssoRecommander
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
