<?php

/*
|--------------------------------------------------------------------------
| clear-cache.php — InfinityFree helper
|--------------------------------------------------------------------------
| InfinityFree has no SSH, so upload this file to the project root
| (same folder as artisan), visit it once in your browser:
|
|     https://your-domain.freehosting.dev/clear-cache.php
|
| It clears Laravel's config/cache/view caches and verifies the
| writable folders exist. DELETE THIS FILE from the server afterwards!
*/

// Safety: only run from the browser in production-like environments
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    echo "Run this via your browser, not the CLI (or remove this check if intentional).\n";
    exit(1);
}

echo '<div style="font-family:sans-serif;max-width:720px;margin:40px auto;">';
echo '<h1>Church Attendance — cache &amp; setup helper</h1><ol>';

// 1. Ensure writable folders exist (common after FTP upload)
echo '<li><strong>Checking folders...</strong><ul>';
foreach ([
    'storage/framework/views',
    'storage/framework/sessions',
    'storage/framework/cache/data',
    'storage/logs',
    'bootstrap/cache',
] as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (! is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    $ok = is_dir($path) && is_writable($path);
    echo '<li style="color:' . ($ok ? 'green' : 'red') . ';">' . htmlspecialchars($dir) .
         ($ok ? ' — OK (writable)' : ' — MISSING or NOT WRITABLE (set CHMOD 755 in the file manager)') . '</li>';
}
echo '</ul></li>';

// 2. Bootstrap Laravel and clear all caches
echo '<li><strong>Clearing caches...</strong><ul>';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $command) {
    $exitCode = $kernel->call($command);
    $output = trim($kernel->output());
    echo '<li style="color:' . ($exitCode === 0 ? 'green' : 'red') . ';">' . htmlspecialchars($command) .
         ($output !== '' ? ' — ' . htmlspecialchars($output) : '') . '</li>';
}
echo '</ul></li>';

// 3. Quick checks
echo '<li><strong>Environment checks...</strong><ul>';
$checks = [
    '.env exists'                    => file_exists(__DIR__ . '/.env'),
    'vendor/autoload.php exists'     => file_exists(__DIR__ . '/vendor/autoload.php'),
    'public/build/manifest.json exists (CSS/JS)' => file_exists(__DIR__ . '/public/build/manifest.json'),
    'PHP version >= 8.2'             => version_compare(PHP_VERSION, '8.2.0', '>='),
];
foreach ($checks as $label => $ok) {
    echo '<li style="color:' . ($ok ? 'green' : 'red') . ';">' . htmlspecialchars($label) .
         ($ok ? ' — OK' : ' — FAILED') . '</li>';
}
echo '</ul></li>';

echo '</ol>';
echo '<p style="color:#e67e22;"><strong>NOW DELETE THIS FILE FROM THE SERVER!</strong></p>';
echo '</div>';
