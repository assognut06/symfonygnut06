<?php

namespace App\Application\DTO\Tih;

final readonly class RateFilterDTO
{
    public function __construct(
        public ?float $minRate,
        public ?float $maxRate,
        public ?string $rateType,
    ) {}
}
