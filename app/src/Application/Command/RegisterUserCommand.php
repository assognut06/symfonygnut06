<?php

namespace App\Application\Command;

use App\Domain\ValueObject\UserType;

class RegisterUserCommand
{
    public function __construct(
        private readonly string $email,
        private readonly string $plainPassword,
        private readonly UserType $userType
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function isTih(): bool
    {
        return $this->userType->isTih();
    }
}
