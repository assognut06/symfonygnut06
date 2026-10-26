<?php

namespace App\Security\OAuth;

enum OAuthProvider: string
{
    case Google = 'google';
    case Microsoft = 'microsoft';

    public function routeName(): string
    {
        return self::Google === $this ? 'connect_google_start' : 'connect_outlook_start';
    }
}
