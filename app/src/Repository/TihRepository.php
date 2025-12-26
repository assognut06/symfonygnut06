<?php

namespace App\Repository;

use App\Application\DTO\Tih\AvailabilityFilterDTO;
use App\Application\DTO\Tih\AvailableFiltersDTO;
use App\Application\DTO\Tih\CityFilterDTO;
use App\Application\DTO\Tih\SkillFilterDTO;
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
                    $qb->expr()->like('t.lastName', ':searchLike'),
                    $qb->expr()->like('t.firstName', ':searchLike'),
                    $qb->expr()->like('t.city', ':searchLike'),
                    $qb->expr()->like('t.address', ':searchLike'),
                    $qb->expr()->like('t.professionalEmail', ':searchLike'),
                    $qb->expr()->like('t.availability', ':searchLike'),
                    $qb->expr()->like('t.postalCode', ':searchLike'),
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

    /**
     * Search TIH profiles with filters and pagination
     * 
     * @param array $filters ['skills' => [], 'cities' => [], 'availability' => []]
     * @param int $page
     * @param int $limit
     * @return Paginator
     */
    public function searchWithFilters(array $filters, int $page = 1, int $limit = 12): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.isValidate = :validated')
            ->setParameter('validated', true);

        // Filter by skills - using subquery to avoid GROUP BY issues
        if (!empty($filters['skills'])) {
            $subQuery = $this->createQueryBuilder('t2')
                ->select('t2.id')
                ->leftJoin('t2.competences', 'c2')
                ->where('c2.id IN (:skills)')
                ->getDQL();
            
            $qb->andWhere($qb->expr()->in('t.id', $subQuery))
               ->setParameter('skills', $filters['skills']);
        }

        // Filter by cities
        if (!empty($filters['cities'])) {
            $qb->andWhere('t.city IN (:cities)')
               ->setParameter('cities', $filters['cities']);
        }

        // Filter by availability
        if (!empty($filters['availability'])) {
            $qb->andWhere('t.availability IN (:availability)')
               ->setParameter('availability', $filters['availability']);
        }

        $qb->orderBy('t.createdAt', 'DESC');

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * Get available filter options with counts based on current filters
     * 
     * @param array $filters Current filters applied
     * @return AvailableFiltersDTO
     */
    public function getAvailableFilters(array $filters = []): AvailableFiltersDTO
    {
        // Base query for validated TIH
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.competences', 'c')
            ->where('t.isValidate = :validated')
            ->setParameter('validated', true);

        // Apply existing filters except the one we're getting options for
        $tempFilters = $filters;
        
        // Get available skills with count
        $qbSkills = clone $qb;
        if (!empty($tempFilters['cities'])) {
            $qbSkills->andWhere('t.city IN (:cities)')
                   ->setParameter('cities', $tempFilters['cities']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbSkills->andWhere('t.availability IN (:availability)')
                   ->setParameter('availability', $tempFilters['availability']);
        }
        $skills = $qbSkills->select('c.id', 'c.name', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('c.id IS NOT NULL')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available cities with count and postal code
        $qbCities = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbCities->andWhere('c.id IN (:skills)')
                     ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbCities->andWhere('t.availability IN (:availability)')
                     ->setParameter('availability', $tempFilters['availability']);
        }
        $cities = $qbCities->select('t.city', 't.postalCode', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.city IS NOT NULL')
            ->groupBy('t.city', 't.postalCode')
            ->orderBy('t.city', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available availability options with count
        $qbAvailability = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbAvailability->andWhere('c.id IN (:skills)')
                    ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['cities'])) {
            $qbAvailability->andWhere('t.city IN (:cities)')
                    ->setParameter('cities', $tempFilters['cities']);
        }
        $availability = $qbAvailability->select('t.availability', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.availability IS NOT NULL')
            ->groupBy('t.availability')
            ->orderBy('t.availability', 'ASC')
            ->getQuery()
            ->getResult();

        return new AvailableFiltersDTO(
            skills: array_map(fn($data) => SkillFilterDTO::fromArray($data), $skills),
            cities: array_map(fn($data) => CityFilterDTO::fromArray($data), $cities),
            availability: array_map(fn($data) => AvailabilityFilterDTO::fromArray($data), $availability)
        );
    }
}
