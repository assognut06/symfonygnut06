<?php

namespace App\Tests\Stub;

use App\Service\RecaptchaVerifier;
use Symfony\Component\HttpFoundation\Request;

/**
 * Always accepts reCAPTCHA in functional tests (APP_ENV=test).
 */
class RecaptchaVerifierStub extends RecaptchaVerifier
{
    public function __construct()
    {
    }

    public function verify(Request $request): bool
    {
        return true;
    }
}
