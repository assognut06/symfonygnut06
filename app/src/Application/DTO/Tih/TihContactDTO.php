<?php

namespace App\Application\DTO\Tih;

class TihContactDTO
{
    public function __construct(
        public string $nom = '',
        public string $prenom = '',
        public ?string $entreprise = null,
        public string $telephone = '',
        public string $email = '',
        public string $subject = '',
        public string $message = '',
    ) {}
}
