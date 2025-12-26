<?php

namespace App\Repository;

use App\Entity\Tih;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Tih>
 */
class TihRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tih::class);
    }

    /**
     * Search TIH profiles with full-text search and pagination
     * 
     * @param string|null $searchTerm
     * @param int $page
     * @param int $limit
     * @return Paginator
     */
    public function searchWithPagination(?string $searchTerm, int $page = 1, int $limit = 12): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.competences', 'c')
            ->addSelect('c')
            ->where('t.isValidate = :validated')
            ->setParameter('validated', true);

        // If search term is provided, use LIKE search
        if ($searchTerm && strlen(trim($searchTerm)) > 0) {
            $searchTerm = trim($searchTerm);
            $searchLike = '%' . $searchTerm . '%';
            
            // Use LIKE for searching across multiple fields
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.nom', ':searchLike'),
                    $qb->expr()->like('t.prenom', ':searchLike'),
                    $qb->expr()->like('t.ville', ':searchLike'),
                    $qb->expr()->like('t.adresse', ':searchLike'),
                    $qb->expr()->like('t.emailPro', ':searchLike'),
                    $qb->expr()->like('t.disponibilite', ':searchLike'),
                    $qb->expr()->like('t.codePostal', ':searchLike'),
                    $qb->expr()->like('c.name', ':searchLike')
                )
            )
            ->setParameter('searchLike', $searchLike);
        }

        $qb->orderBy('t.createdAt', 'DESC');

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * Count total validated TIH profiles
     */
    public function countValidated(): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.isValidate = :validated')
            ->setParameter('validated', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
