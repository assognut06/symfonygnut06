<?php

/**
 * Fails CI when line coverage drops below configured thresholds for critical code.
 *
 * Usage: php scripts/check-coverage-threshold.php var/coverage/clover.xml
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/check-coverage-threshold.php <clover.xml>\n");
    exit(1);
}

$cloverFile = $argv[1];
if (!is_readable($cloverFile)) {
    fwrite(STDERR, "Coverage file not found: {$cloverFile}\n");
    exit(1);
}

/** @var array<string, float> controller basename => minimum line coverage % */
$thresholds = [
    'AdminTihController.php' => 90.0,
    'AdminUserController.php' => 40.0,
];

$xml = simplexml_load_file($cloverFile);
if ($xml === false) {
    fwrite(STDERR, "Unable to parse Clover XML.\n");
    exit(1);
}

$metrics = [];
foreach ($xml->project->file as $file) {
    $path = (string) $file['name'];
    $basename = basename($path);
    $statements = (int) $file->metrics['statements'];
    $covered = (int) $file->metrics['coveredstatements'];

    if ($statements === 0) {
        $metrics[$basename] = 100.0;
        continue;
    }

    $metrics[$basename] = round(($covered / $statements) * 100, 2);
}

$failed = false;
foreach ($thresholds as $file => $minimum) {
    if (!isset($metrics[$file])) {
        fwrite(STDERR, sprintf("[coverage] MISSING  %s (required >= %.1f%%)\n", $file, $minimum));
        $failed = true;
        continue;
    }

    $actual = $metrics[$file];
    $status = $actual >= $minimum ? 'OK' : 'FAIL';
    $line = sprintf(
        '[coverage] %s  %s: %.2f%% (required >= %.1f%%)',
        $status,
        $file,
        $actual,
        $minimum
    );

    if ($actual >= $minimum) {
        echo $line . PHP_EOL;
    } else {
        fwrite(STDERR, $line . PHP_EOL);
        $failed = true;
    }
}

echo PHP_EOL . 'Admin controller line coverage:' . PHP_EOL;
foreach ($metrics as $file => $percent) {
    if (str_starts_with($file, 'Admin') && str_ends_with($file, 'Controller.php')) {
        echo sprintf("  - %s: %.2f%%\n", $file, $percent);
    }
}

exit($failed ? 1 : 0);
