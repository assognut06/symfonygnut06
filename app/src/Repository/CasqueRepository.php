<?php

namespace App\Repository;

use App\Entity\Casque;
use App\Entity\Don;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Casque>
 */
class CasqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Casque::class);
    }

    public function TrouveCasquesParDon(Don $don): Query
    {
        return $this->createQueryBuilder('c')
             ->where('c.don = :don')
             ->setParameter('don', $don) // Injecter l'entité Don
             ->orderBy('c.id', 'DESC') // Tri par ID
             ->getQuery();
    }

    //    /**
    //     * @return Casque[] Returns an array of Casque objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Casque
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
