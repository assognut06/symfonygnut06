<?php

namespace App\Repository;

use App\Entity\HelloAssoFormNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HelloAssoFormNotification>
 */
class HelloAssoFormNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelloAssoFormNotification::class);
    }

    /**
     * Trouve les notifications par slug d'organisation
     */
    public function findByOrganizationSlug(string $organizationSlug): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.organizationSlug = :organizationSlug')
            ->setParameter('organizationSlug', $organizationSlug)
            ->orderBy('h.meta.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notifications récentes
     */
    public function findRecentNotifications(int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.meta.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notifications par type d'événement
     */
    public function findByEventType(string $eventType): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.eventType = :eventType')
            ->setParameter('eventType', $eventType)
            ->orderBy('h.meta.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notifications par slug de formulaire
     */
    public function findByFormSlug(string $formSlug): ?HelloAssoFormNotification
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.formSlug = :formSlug')
            ->setParameter('formSlug', $formSlug)
            ->orderBy('h.meta.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les notifications par organisation
     */
    public function countByOrganization(string $organizationSlug): int
    {
        return $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.organizationSlug = :organizationSlug')
            ->setParameter('organizationSlug', $organizationSlug)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les formulaires actifs (état Public)
     */
    public function findActiveForms(): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.state = :state')
            ->setParameter('state', 'Public')
            ->orderBy('h.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}