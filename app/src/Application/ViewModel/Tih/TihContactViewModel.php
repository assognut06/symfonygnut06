<?php

namespace App\Application\ViewModel\Tih;

use App\Entity\Tih;

final readonly class TihContactViewModel
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $fullName,
    ) {}

    public static function fromEntity(Tih $tih): self
    {
        return new self(
            id: $tih->getId(),
            firstName: $tih->getFirstName() ?? '',
            lastName: $tih->getLastName() ?? '',
            fullName: trim(($tih->getFirstName() ?? '') . ' ' . ($tih->getLastName() ?? ''))
        );
    }
}
