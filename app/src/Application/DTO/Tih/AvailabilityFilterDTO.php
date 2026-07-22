<?php

namespace App\Application\DTO\Tih;

final readonly class AvailabilityFilterDTO
{
    public function __construct(
        public string $availability,
        public int $count
    ) {
    }
/**
 * @param array{availability:string,count:int} $data
 */
    public static function fromArray(array $data): self
    {
        return new self(
            availability: (string) $data['availability'],
            count: (int) $data['count']
        );
    }
}
