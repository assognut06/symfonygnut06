<?php

namespace App\Domain\ValueObject;

final class UserType
{
    private const TYPE_REGULAR = 'regular';
    private const TYPE_TIH = 'tih';

    private function __construct(
        private readonly string $value
    ) {
        if (!in_array($value, [self::TYPE_REGULAR, self::TYPE_TIH])) {
            throw new \InvalidArgumentException('Invalid user type');
        }
    }

    public static function regular(): self
    {
        return new self(self::TYPE_REGULAR);
    }

    public static function tih(): self
    {
        return new self(self::TYPE_TIH);
    }

    public function isTih(): bool
    {
        return $this->value === self::TYPE_TIH;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRoles(): array
    {
        return match($this->value) {
            self::TYPE_TIH => ['ROLE_USER', 'ROLE_TIH'],
            default => ['ROLE_USER'],
        };
    }
}
