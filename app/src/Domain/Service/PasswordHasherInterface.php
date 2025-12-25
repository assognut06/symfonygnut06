<?php

namespace App\Domain\Service;

interface PasswordHasherInterface
{
    public function hashPassword(object $user, string $plainPassword): string;

    public function isPasswordValid(object $user, string $plainPassword): bool;
}
