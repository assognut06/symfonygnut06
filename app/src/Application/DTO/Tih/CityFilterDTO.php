<?php

namespace App\Application\DTO\Tih;

final readonly class CityFilterDTO
{
    public function __construct(
        public string $city,
        public string $postalCode,
        public int $count
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            city: (string) $data['city'],
            postalCode: (string) $data['postalCode'],
            count: (int) $data['count']
        );
    }
}
