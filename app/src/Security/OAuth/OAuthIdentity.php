<?php

namespace App\Security\OAuth;

final readonly class OAuthIdentity
{
    public function __construct(
        public OAuthProvider $provider,
        public string $subject,
        public ?string $email,
        public bool $emailVerified,
    ) {
    }
}
