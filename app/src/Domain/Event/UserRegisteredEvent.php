<?php

namespace App\Domain\Event;

final class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly bool $isTih,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {
    }
}
