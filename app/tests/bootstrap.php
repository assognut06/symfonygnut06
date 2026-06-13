<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$defaults = [
    'KERNEL_CLASS' => 'App\\Kernel',
    'APP_ENV' => 'test',
    'APP_SECRET' => 'test',
    'SYMFONY_DEPRECATIONS_HELPER' => '999999',
    'MAILER_DSN' => 'null://null',
    'APICLIENTID' => 'test',
    'APICLIENTSECRET' => 'test',
    'SLUGASSO' => 'gnut-06',
    'GNUT06MAPAPI' => 'test',
    'APIMAPGEOCODING' => 'test',
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
    'HELLOASSO_WEBHOOK_SECRET' => 'test-webhook-secret',
];

foreach ($defaults as $name => $value) {
    $_ENV[$name] = $_ENV[$name] ?? $_SERVER[$name] ?? $value;
    $_SERVER[$name] = $_SERVER[$name] ?? $_ENV[$name];
    putenv(sprintf('%s=%s', $name, $_ENV[$name]));
}

/**
 * CI checkouts do not run npm build (public/build is gitignored).
 * Admin templates call Encore and asset() which require these files.
 */
function ensureWebpackBuildStubs(string $projectDir): void
{
    $buildDir = $projectDir.'/public/build';
    $manifest = $buildDir.'/manifest.json';
    $entrypoints = $buildDir.'/entrypoints.json';

    if (is_file($manifest) && is_file($entrypoints)) {
        return;
    }

    if (!is_dir($buildDir)) {
        mkdir($buildDir, 0777, true);
    }

    if (!is_file($manifest)) {
        file_put_contents($manifest, "{}\n");
    }

    if (!is_file($entrypoints)) {
        file_put_contents($entrypoints, json_encode([
            'entrypoints' => [
                'app' => ['js' => [], 'css' => []],
                'profil' => ['js' => [], 'css' => []],
                'tih_search' => ['js' => [], 'css' => []],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }
}

ensureWebpackBuildStubs(dirname(__DIR__));

$argv = $_SERVER['argv'] ?? [];
$isUnitTestsuiteOnly = in_array('--testsuite=Unit', $argv, true);

if (!$isUnitTestsuiteOnly) {
    $kernel = new App\Kernel('test', true);
    $kernel->boot();

    $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    $schemaTool = new Doctrine\ORM\Tools\SchemaTool($entityManager);
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
    $schemaTool->dropSchema($metadata);
    $schemaTool->createSchema($metadata);

    $kernel->shutdown();
}