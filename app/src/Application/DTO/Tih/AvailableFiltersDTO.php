<?php

namespace App\Application\DTO\Tih;

final readonly class AvailableFiltersDTO
{
    /**
     * @param SkillFilterDTO[] $skills
     * @param RegionFilterDTO[] $regions
     * @param DepartementFilterDTO[] $departements
     * @param AvailabilityFilterDTO[] $availability
     * @param RateTypeFilterDTO[] $rateTypes
     */
    public function __construct(
        public array $skills,
        public array $regions,
        public array $departements,
        public array $availability,
        public array $rateTypes,
        public ?float $minRate,
        public ?float $maxRate,
        public ?\DateTimeInterface $earliestAvailabilityDate,
    ) {
    }
}
