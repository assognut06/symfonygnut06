<?php

namespace App\Application\DTO\Tih;

final readonly class DepartementFilterDTO
{
    public function __construct(
        public string $departement,
        public int $count,
        public ?string $region = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            departement: $data['departement'],
            count: (int)$data['count'],
            region: $data['region'] ?? null
        );
    }
}
