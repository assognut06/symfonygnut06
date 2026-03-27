<?php

namespace App\Infrastructure\Service;

use App\Domain\Service\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function hashPassword(object $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }

    public function isPasswordValid(object $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }
}
