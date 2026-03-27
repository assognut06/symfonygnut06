<?php

namespace App\Domain\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user, bool $flush = false): void;

    public function remove(User $user, bool $flush = false): void;

    public function findOneByEmail(string $email): ?User;

    public function findById(int $id): ?User;
}
