<?php

namespace App\Application\DTO\Tih;

final readonly class AvailableFiltersDTO
{
    /**
     * @param SkillFilterDTO[] $skills
     * @param CityFilterDTO[] $cities
     * @param AvailabilityFilterDTO[] $availability
     */
    public function __construct(
        public array $skills,
        public array $cities,
        public array $availability
    ) {
    }
}
