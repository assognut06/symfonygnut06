<?php

namespace App\Application\DTO\Tih;

class TihContactDTO
{
    public function __construct(
        public readonly string $nom,
        public readonly string $prenom,
        public readonly ?string $entreprise,
        public readonly string $telephone,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
    ) {}
}
