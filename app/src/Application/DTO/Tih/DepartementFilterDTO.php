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
/**
 * @param array{departement:string,region:?string,count:int} $data
 */
    public static function fromArray(array $data): self
    {
        return new self(
            departement: $data['departement'],
            count: (int)$data['count'],
            region: $data['region'] ?? null
        );
    }
}
