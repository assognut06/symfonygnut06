<?php

namespace App\Repository;

use App\Entity\Don;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Don::class);
    }

    /**
     * Récupère tous les dons avec leurs détails.
     */
    public function findAllWithDetails()
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.donateur', 'donateur') // Jointure avec l'entité Donateur
            ->addSelect('donateur') // Sélectionne les données du donateur
            ->orderBy('d.date_creation', 'DESC') // Trie par date de création (du plus récent au plus ancien)
            ->getQuery()
            ->getResult();
    }
    
    public function TrouveDonsParDonateur($donateur)
    {
        return $this->createQueryBuilder('d')
            ->where('d.donateur = :donateur') 
            ->setParameter('donateur', $donateur)
            ->orderBy('d.date_creation', 'DESC') 
            ->getQuery();
    }


}