<?php

namespace App\Security\OAuth;

use App\Entity\User;

final class LegacyMicrosoftRepairRequired extends OAuthAccountException
{
    public function __construct(private readonly User $user, private readonly OAuthIdentity $identity)
    {
        parent::__construct('Nous devons confirmer votre adresse email avant de sécuriser cette ancienne connexion Microsoft.');
    }

    public function user(): User { return $this->user; }
    public function identity(): OAuthIdentity { return $this->identity; }
}
