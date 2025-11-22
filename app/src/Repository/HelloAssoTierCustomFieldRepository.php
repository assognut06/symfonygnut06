<?php

namespace App\Repository;

use App\Entity\HelloAssoTierCustomField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HelloAssoTierCustomField>
 */
class HelloAssoTierCustomFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelloAssoTierCustomField::class);
    }

    /**
     * Trouve les champs par tier
     */
    public function findByTier(string $tierId): array
    {
        return $this->createQueryBuilder('cf')
            ->andWhere('cf.tier = :tierId')
            ->setParameter('tierId', $tierId)
            ->orderBy('cf.externalId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les champs requis par tier
     */
    public function findRequiredByTier(string $tierId): array
    {
        return $this->createQueryBuilder('cf')
            ->andWhere('cf.tier = :tierId')
            ->andWhere('cf.isRequired = :required')
            ->setParameter('tierId', $tierId)
            ->setParameter('required', true)
            ->orderBy('cf.externalId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve par ID externe
     */
    public function findByExternalId(int $externalId): ?HelloAssoTierCustomField
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Trouve les champs par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('cf')
            ->andWhere('cf.type = :type')
            ->setParameter('type', $type)
            ->orderBy('cf.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les champs obligatoires par tier
     */
    public function countRequiredFieldsByTier(string $tierId): int
    {
        return $this->createQueryBuilder('cf')
            ->select('COUNT(cf.id)')
            ->andWhere('cf.tier = :tierId')
            ->andWhere('cf.isRequired = :required')
            ->setParameter('tierId', $tierId)
            ->setParameter('required', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}