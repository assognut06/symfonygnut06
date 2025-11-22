<?php

namespace App\Repository;

use App\Entity\HelloAssoTier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HelloAssoTier>
 */
class HelloAssoTierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelloAssoTier::class);
    }

    /**
     * Trouve les tiers par formulaire
     */
    public function findByForm(string $formId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.form = :formId')
            ->setParameter('formId', $formId)
            ->orderBy('t.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un tier par son ID externe
     */
    public function findByExternalId(int $externalId): ?HelloAssoTier
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Trouve les tiers favoris d'un formulaire
     */
    public function findFavoritesByForm(string $formId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.form = :formId')
            ->andWhere('t.isFavorite = :favorite')
            ->setParameter('formId', $formId)
            ->setParameter('favorite', true)
            ->orderBy('t.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tiers dans une gamme de prix
     */
    public function findByPriceRange(string $minPrice, string $maxPrice): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.price >= :minPrice')
            ->andWhere('t.price <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('t.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tiers éligibles au reçu fiscal
     */
    public function findTaxReceiptEligible(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isEligibleTaxReceipt = :eligible')
            ->setParameter('eligible', true)
            ->orderBy('t.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}