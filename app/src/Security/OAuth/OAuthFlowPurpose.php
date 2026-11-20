<?php

namespace App\Security\OAuth;

enum OAuthFlowPurpose: string
{
    case Login = 'login';
    case Link = 'link';
    case Reauthenticate = 'reauthenticate';
}
