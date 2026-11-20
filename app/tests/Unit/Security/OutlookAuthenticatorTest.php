<?php

namespace App\Tests\Unit\Security;

use App\Security\OutlookAuthenticator;
use PHPUnit\Framework\TestCase;

final class OutlookAuthenticatorTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, bool}> */
    public static function claimsProvider(): iterable
    {
        yield 'personal Microsoft account' => [['tid' => '9188040d-6c67-4c5b-b112-36a304b66dad', 'email' => 'a@outlook.com'], true];
        yield 'work account with verified email domain' => [['tid' => '11111111-2222-3333-4444-555555555555', 'xms_edov' => true], true];
        yield 'work account without xms_edov' => [['tid' => '11111111-2222-3333-4444-555555555555', 'email' => 'a@contoso.test'], false];
        yield 'work account with unverified email domain' => [['tid' => '11111111-2222-3333-4444-555555555555', 'xms_edov' => false], false];
        yield 'xms_edov sent as a string' => [['tid' => '11111111-2222-3333-4444-555555555555', 'xms_edov' => 'true'], false];
        yield 'no tenant' => [[], false];
    }

    /**
     * @dataProvider claimsProvider
     *
     * @param array<string, mixed> $claims
     */
    public function testEmailIsVerifiedOnlyWhenMicrosoftProvesOwnership(array $claims, bool $expected): void
    {
        self::assertSame($expected, OutlookAuthenticator::isEmailVerified($claims));
    }
}
