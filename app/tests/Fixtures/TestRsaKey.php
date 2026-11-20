<?php

namespace App\Tests\Fixtures;

/** Test-only RSA key pair for signing fake ID tokens, never used outside the test suite. */
final class TestRsaKey
{
    public const KID = 'test-key';

    public const PRIVATE_KEY = <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDRB2ZMCXexa7Dc
        bqMBWrNylYxiyg3eeM74H3BcSS25Nz2w6d9w5GjHP1mlikHvZpSKNo/LW1i7mk16
        06jCr0LRI7PWgqfhU00pXj57HoOnzS/glHAWpKAAUG+XpZ8PEDj8vQ4WWVV6xdRy
        xPA0xRwFUnmV1y6+YkfyUx9oOH2QTMHNWZd3wyTHWZ8KmnNarw48wToWkh45qayn
        vmmpTSUBCMKOGLIT3epM/mswYfUomv5XVDtKlQxXJbWd/yAK0RGTVL22bkex5WQy
        m/NuTH8IK4+qZQyum02KiPeo6g3LNv+4rbwxjsJSFeWFSVz01PTgNE3KeoceO0bG
        Mj5BbCtLAgMBAAECggEAGCO7IB/dx0sRBzvtrjvbymlT55q/BEi+WjBDSR0YXzHu
        eW5g5Ag0w4Hg5/myCKQ3lkibzZfUhQHaXctwy17zH/T4EVdQbPiySgs8uvo4qRnM
        pCpwUWUcpzyizogNeO9eLW3l4RXbBc0v7jspJGb5B/JQ4UmS9+Cgv27zCxWvBolp
        PghisXvuI6K8K2/W31Si1Kq9Hpow3UK649c5g7P4qHtNo3p1vNYsJaxIzvjOmJMC
        HTFMP/yzFy/FyWmCg84KYO+6N0swttCyQK2YroowlsOovf656zu62SbDqGJSI63W
        HEgWZ9tMwdetXIEMNnSxo7v+F8dV0RJQH5uYbi1xmQKBgQDzkdtHTwatCAvcaHFf
        akRHmUSXHdTQLzWgKtpUSqrIejKFgZAjFm8veQVjU7EPlQP0bchZSAgQZdJXsGoU
        5ICb3mElzUhjO07g8jpdZCQ6/vvv07J/EgMDlByDRoYqEiG/izAk7yeYgjNfbFSV
        q1L5iQr/b1yTfKFNhsPDIsOEmQKBgQDbskfH0T7FqNk95pHsTt8XtJmDUT9YCZ8V
        0izg5L+bebsxGXN2NBzbvpByP4FNtX7Uu8j0au89zXn2IbIRPowv/tqxiDGLb+10
        vrzDtwVwbpVM4Qx+w5+Knzh0wFroiv+CO5ay+vnOIE36ID+XOPiEJUy53+FVyvbd
        ogFhCCt5gwKBgQDTgd8WxysW6pvSI+f/YTo1qoSDbWY1+ijpEw1QkR5IxMRGZsIR
        lhOq976UCEMDMvWiNgr6bLCD/MdxWkJkLiD4OV3HA8JOWVwfvnisTJ+hk3aXRhAE
        hFGVs/ImlQFAW0pvGKEQEZUivD18KYgyB/ofsr+YHM4ZTOqNde9c7j02UQKBgEtl
        PIMTiUJWNu+qYCvDyYYeIYzSZjW1X5YigepQNn2J4jbwcBKBweGb3YCH0L01ayhg
        pY9T33TLPm68k5qdZ4jVIoJIphAfQlONXcSg28oA+VXf6eTbB7aP+9T9anVhtlwg
        TRBxVydpKLmNNaWVFJxtHI6xiWhi9iOLhIOjRSA3AoGBAIY69kDK/p5ZjR92kap8
        ZIIvKFsKqid8u5HaX0AdDMkOghM3Y2TFN3sOzta2/SO0ctX3TnrUQjVIwkLVK7cG
        GwGu5wlixdJRQyermGbkrrSD34KtkUUUOlJepRBsTLD74guEHt2YonH9boU6OvsM
        swRlCMNhvDc1mzI6O76GSfEA
        -----END PRIVATE KEY-----
        PEM;

    /** @return array<string, string> */
    public static function publicJwk(): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private(self::PRIVATE_KEY));
        $encode = static fn (string $binary): string => rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');

        return [
            'kty' => 'RSA',
            'kid' => self::KID,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $encode($details['rsa']['n']),
            'e' => $encode($details['rsa']['e']),
        ];
    }
}
