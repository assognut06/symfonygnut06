<?php

namespace App\Application\DTO\Tih;

final readonly class RegionFilterDTO
{
    public function __construct(
        public string $region,
        public int $count
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            region: $data['region'],
            count: (int)$data['count']
        );
    }
}
