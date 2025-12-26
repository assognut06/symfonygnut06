<?php

namespace App\Application\DTO\Tih;

final readonly class SkillFilterDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $count
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            count: (int) $data['count']
        );
    }
}
