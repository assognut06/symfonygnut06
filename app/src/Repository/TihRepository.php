<?php

namespace App\Repository;

use App\Application\DTO\Tih\AvailabilityFilterDTO;
use App\Application\DTO\Tih\AvailableFiltersDTO;
use App\Application\DTO\Tih\RegionFilterDTO;
use App\Application\DTO\Tih\DepartementFilterDTO;
use App\Application\DTO\Tih\RateTypeFilterDTO;
use App\Application\DTO\Tih\SkillFilterDTO;
use App\Entity\Tih;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**F
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
     * @param array $filters ['skills' => [], 'cities' => [], 'availability' => [], 'minRate' => float, 'maxRate' => float, 'rateType' => string, 'availabilityDate' => \DateTime]
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

        // Filter by regions
        if (!empty($filters['regions'])) {
            $qb->andWhere('t.region IN (:regions)')
               ->setParameter('regions', $filters['regions']);
        }

        // Filter by départements
        if (!empty($filters['departements'])) {
            $qb->andWhere('t.departement IN (:departements)')
               ->setParameter('departements', $filters['departements']);
        }

        // Filter by availability (cumulative - broader options include narrower ones)
        if (!empty($filters['availability'])) {
            $availabilityOptions = $filters['availability'];
            $cumulativeOptions = [];
            
            // Define availability hierarchy
            $hierarchy = [
                'Immédiate' => ['Immédiate'],
                'Sous 15 jours' => ['Immédiate', 'Sous 15 jours'],
                'Sous 1 mois' => ['Immédiate', 'Sous 15 jours', 'Sous 1 mois'],
                'Sous 3 mois' => ['Immédiate', 'Sous 15 jours', 'Sous 1 mois', 'Sous 3 mois']
            ];
            
            // For each selected option, add all included options
            foreach ($availabilityOptions as $option) {
                if (isset($hierarchy[$option])) {
                    $cumulativeOptions = array_merge($cumulativeOptions, $hierarchy[$option]);
                } else {
                    $cumulativeOptions[] = $option;
                }
            }
            
            $cumulativeOptions = array_unique($cumulativeOptions);
            
            $qb->andWhere('t.availability IN (:availability)')
               ->setParameter('availability', $cumulativeOptions);
        }

        // Filter by rate range
        if (isset($filters['minRate']) && $filters['minRate'] !== null && $filters['minRate'] !== '') {
            $qb->andWhere('t.rate >= :minRate')
               ->setParameter('minRate', $filters['minRate']);
        }
        
        if (isset($filters['maxRate']) && $filters['maxRate'] !== null && $filters['maxRate'] !== '') {
            $qb->andWhere('t.rate <= :maxRate')
               ->setParameter('maxRate', $filters['maxRate']);
        }

        // Filter by rate type
        if (!empty($filters['rateType']) && $filters['rateType'] !== 'all') {
            $qb->andWhere('t.rateType = :rateType')
               ->setParameter('rateType', $filters['rateType']);
        }

        // Filter by availability date
        if (isset($filters['availabilityDate']) && $filters['availabilityDate'] instanceof \DateTimeInterface) {
            $qb->andWhere('t.availabilityDate <= :availabilityDate')
               ->setParameter('availabilityDate', $filters['availabilityDate']);
        }
        
        // Filter by availability date (after)
        if (isset($filters['availabilityDateAfter']) && $filters['availabilityDateAfter'] instanceof \DateTimeInterface) {
            $qb->andWhere('t.availabilityDate > :availabilityDateAfter')
               ->setParameter('availabilityDateAfter', $filters['availabilityDateAfter']);
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
        if (isset($tempFilters['minRate']) && $tempFilters['minRate'] !== null) {
            $qbSkills->andWhere('t.rate >= :minRate')
                   ->setParameter('minRate', $tempFilters['minRate']);
        }
        if (isset($tempFilters['maxRate']) && $tempFilters['maxRate'] !== null) {
            $qbSkills->andWhere('t.rate <= :maxRate')
                   ->setParameter('maxRate', $tempFilters['maxRate']);
        }
        if (!empty($tempFilters['rateType']) && $tempFilters['rateType'] !== 'all') {
            $qbSkills->andWhere('t.rateType = :rateType')
                   ->setParameter('rateType', $tempFilters['rateType']);
        }
        $skills = $qbSkills->select('c.id', 'c.name', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('c.id IS NOT NULL')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available regions with count
        $qbRegions = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbRegions->andWhere('c.id IN (:skills)')
                     ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbRegions->andWhere('t.availability IN (:availability)')
                     ->setParameter('availability', $tempFilters['availability']);
        }
        if (isset($tempFilters['minRate']) && $tempFilters['minRate'] !== null) {
            $qbRegions->andWhere('t.rate >= :minRate')
                     ->setParameter('minRate', $tempFilters['minRate']);
        }
        if (isset($tempFilters['maxRate']) && $tempFilters['maxRate'] !== null) {
            $qbRegions->andWhere('t.rate <= :maxRate')
                     ->setParameter('maxRate', $tempFilters['maxRate']);
        }
        if (!empty($tempFilters['rateType']) && $tempFilters['rateType'] !== 'all') {
            $qbRegions->andWhere('t.rateType = :rateType')
                     ->setParameter('rateType', $tempFilters['rateType']);
        }
        if (!empty($tempFilters['departements'])) {
            $qbRegions->andWhere('t.departement IN (:departements)')
                     ->setParameter('departements', $tempFilters['departements']);
        }
        $regions = $qbRegions->select('t.region', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.region IS NOT NULL')
            ->groupBy('t.region')
            ->orderBy('t.region', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available départements with count
        $qbDepartements = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbDepartements->andWhere('c.id IN (:skills)')
                     ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbDepartements->andWhere('t.availability IN (:availability)')
                     ->setParameter('availability', $tempFilters['availability']);
        }
        if (isset($tempFilters['minRate']) && $tempFilters['minRate'] !== null) {
            $qbDepartements->andWhere('t.rate >= :minRate')
                     ->setParameter('minRate', $tempFilters['minRate']);
        }
        if (isset($tempFilters['maxRate']) && $tempFilters['maxRate'] !== null) {
            $qbDepartements->andWhere('t.rate <= :maxRate')
                     ->setParameter('maxRate', $tempFilters['maxRate']);
        }
        if (!empty($tempFilters['rateType']) && $tempFilters['rateType'] !== 'all') {
            $qbDepartements->andWhere('t.rateType = :rateType')
                     ->setParameter('rateType', $tempFilters['rateType']);
        }
        if (!empty($tempFilters['regions'])) {
            $qbDepartements->andWhere('t.region IN (:regions)')
                     ->setParameter('regions', $tempFilters['regions']);
        }
        $departements = $qbDepartements->select('t.departement', 't.region', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.departement IS NOT NULL')
            ->groupBy('t.departement', 't.region')
            ->orderBy('t.departement', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available availability options with count
        $qbAvailability = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbAvailability->andWhere('c.id IN (:skills)')
                    ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['regions'])) {
            $qbAvailability->andWhere('t.region IN (:regions)')
                    ->setParameter('regions', $tempFilters['regions']);
        }
        if (!empty($tempFilters['departements'])) {
            $qbAvailability->andWhere('t.departement IN (:departements)')
                    ->setParameter('departements', $tempFilters['departements']);
        }
        if (isset($tempFilters['minRate']) && $tempFilters['minRate'] !== null) {
            $qbAvailability->andWhere('t.rate >= :minRate')
                    ->setParameter('minRate', $tempFilters['minRate']);
        }
        if (isset($tempFilters['maxRate']) && $tempFilters['maxRate'] !== null) {
            $qbAvailability->andWhere('t.rate <= :maxRate')
                    ->setParameter('maxRate', $tempFilters['maxRate']);
        }
        if (!empty($tempFilters['rateType']) && $tempFilters['rateType'] !== 'all') {
            $qbAvailability->andWhere('t.rateType = :rateType')
                    ->setParameter('rateType', $tempFilters['rateType']);
        }
        $availability = $qbAvailability->select('t.availability', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.availability IS NOT NULL')
            ->groupBy('t.availability')
            ->orderBy('t.availability', 'ASC')
            ->getQuery()
            ->getResult();

        // Get available rate types with count
        $qbRateTypes = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbRateTypes->andWhere('c.id IN (:skills)')
                    ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['regions'])) {
            $qbRateTypes->andWhere('t.region IN (:regions)')
                    ->setParameter('regions', $tempFilters['regions']);
        }
        if (!empty($tempFilters['departements'])) {
            $qbRateTypes->andWhere('t.departement IN (:departements)')
                    ->setParameter('departements', $tempFilters['departements']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbRateTypes->andWhere('t.availability IN (:availability)')
                    ->setParameter('availability', $tempFilters['availability']);
        }
        $rateTypes = $qbRateTypes->select('t.rateType', 'COUNT(DISTINCT t.id) as count')
            ->andWhere('t.rateType IS NOT NULL')
            ->groupBy('t.rateType')
            ->orderBy('t.rateType', 'ASC')
            ->getQuery()
            ->getResult();

        // Get rate range (min and max)
        $qbRateRange = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbRateRange->andWhere('c.id IN (:skills)')
                    ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['regions'])) {
            $qbRateRange->andWhere('t.region IN (:regions)')
                    ->setParameter('regions', $tempFilters['regions']);
        }
        if (!empty($tempFilters['departements'])) {
            $qbRateRange->andWhere('t.departement IN (:departements)')
                    ->setParameter('departements', $tempFilters['departements']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbRateRange->andWhere('t.availability IN (:availability)')
                    ->setParameter('availability', $tempFilters['availability']);
        }
        $rateStats = $qbRateRange->select('MIN(t.rate) as minRate', 'MAX(t.rate) as maxRate')
            ->andWhere('t.rate IS NOT NULL')
            ->getQuery()
            ->getSingleResult();

        // Get earliest availability date
        $qbEarliestDate = clone $qb;
        if (!empty($tempFilters['skills'])) {
            $qbEarliestDate->andWhere('c.id IN (:skills)')
                    ->setParameter('skills', $tempFilters['skills']);
        }
        if (!empty($tempFilters['regions'])) {
            $qbEarliestDate->andWhere('t.region IN (:regions)')
                    ->setParameter('regions', $tempFilters['regions']);
        }
        if (!empty($tempFilters['departements'])) {
            $qbEarliestDate->andWhere('t.departement IN (:departements)')
                    ->setParameter('departements', $tempFilters['departements']);
        }
        if (!empty($tempFilters['availability'])) {
            $qbEarliestDate->andWhere('t.availability IN (:availability)')
                    ->setParameter('availability', $tempFilters['availability']);
        }
        $earliestDateResult = $qbEarliestDate->select('MIN(t.availabilityDate) as earliestDate')
            ->andWhere('t.availabilityDate IS NOT NULL')
            ->getQuery()
            ->getSingleResult();

        // Convert earliest date string to DateTime object
        $earliestDate = null;
        if ($earliestDateResult['earliestDate']) {
            $earliestDate = $earliestDateResult['earliestDate'] instanceof \DateTimeInterface 
                ? $earliestDateResult['earliestDate'] 
                : new \DateTime($earliestDateResult['earliestDate']);
        }

        return new AvailableFiltersDTO(
            skills: array_map(fn($data) => SkillFilterDTO::fromArray($data), $skills),
            regions: array_map(fn($data) => RegionFilterDTO::fromArray($data), $regions),
            departements: array_map(fn($data) => DepartementFilterDTO::fromArray($data), $departements),
            availability: array_map(fn($data) => AvailabilityFilterDTO::fromArray($data), $availability),
            rateTypes: array_map(fn($data) => RateTypeFilterDTO::fromArray($data), $rateTypes),
            minRate: $rateStats['minRate'] ? (float)$rateStats['minRate'] : null,
            maxRate: $rateStats['maxRate'] ? (float)$rateStats['maxRate'] : null,
            earliestAvailabilityDate: $earliestDate
        );
    }
}
