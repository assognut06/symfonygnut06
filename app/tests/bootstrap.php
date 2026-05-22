<?php

require dirname(__DIR__).'/vendor/autoload.php';

$defaults = [
    'KERNEL_CLASS' => 'App\\Kernel',
    'APP_ENV' => 'test',
    'APP_SECRET' => 'test',
    'SYMFONY_DEPRECATIONS_HELPER' => '999999',
    'DATABASE_URL' => 'sqlite:///:memory:',
    'MAILER_DSN' => 'null://null',
    'APICLIENTID' => 'test',
    'APICLIENTSECRET' => 'test',
    'SLUGASSO' => 'gnut-06',
    'GNUT06MAPAPI' => 'test',
    'APIMAILJET' => 'test',
    'APIMAILJETSECRET' => 'test',
    'SECRETOHME' => 'test',
    'APIFRAMEVR' => 'test',
    'NOCAPTCHA_SECRET' => 'test',
    'NOCAPTCHA_SITEKEY' => 'test',
    'GOOGLE_CLIENT_ID' => 'test',
    'GOOGLE_CLIENT_SECRET' => 'test',
    'AZURE_CLIENT_ID' => 'test',
    'AZURE_CLIENT_SECRET' => 'test',
];

foreach ($defaults as $name => $value) {
    $_ENV[$name] = $_ENV[$name] ?? $_SERVER[$name] ?? $value;
    $_SERVER[$name] = $_SERVER[$name] ?? $_ENV[$name];
    putenv(sprintf('%s=%s', $name, $_ENV[$name]));
}
