<?php

/*
|--------------------------------------------------------------------------
| InfinityFree / Shared Hosting Front Controller
|--------------------------------------------------------------------------
| On InfinityFree the web root (htdocs) is the project root, so this file
| bootstraps Laravel directly from here. A root .htaccess also routes
| requests through /public — this file is the fallback when mod_rewrite
| is unavailable.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ---------------------------------------------------------------
// 1. PHP version guard — Laravel 12 requires PHP 8.2 or newer.
//    On InfinityFree: vPanel -> "PHP Configuration" -> select 8.2/8.3
// ---------------------------------------------------------------
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:24px;border:1px solid #e74c3c;border-radius:8px;background:#fff;">';
    echo '<h1 style="color:#e74c3c;margin-top:0;">PHP version too old</h1>';
    echo '<p>This application requires <strong>PHP 8.2 or newer</strong>, but the server is running <strong>PHP ' . PHP_VERSION . '</strong>.</p>';
    echo '<p><strong>Fix:</strong> In your InfinityFree control panel open <em>"PHP Configuration"</em> and choose <strong>PHP 8.2</strong> or <strong>8.3</strong>, then reload this page.</p>';
    echo '</div>';
    exit(1);
}

// ---------------------------------------------------------------
// 2. Make sure required Laravel storage directories exist.
// ---------------------------------------------------------------
foreach ([
    'storage/framework/views',
    'storage/framework/sessions',
    'storage/framework/cache/data',
    'storage/logs',
    'bootstrap/cache',
] as $requiredDir) {
    $path = __DIR__ . '/' . $requiredDir;
    if (! is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

// ---------------------------------------------------------------
// 3. Friendly checks for missing deployment files.
// ---------------------------------------------------------------
$missing = [];
foreach ([
    'vendor/autoload.php' => 'Upload the complete "vendor" folder (run "composer install" locally first — do NOT upload node_modules).',
    '.env'                => 'Upload your .env file to the project root (htdocs).',
    'bootstrap/app.php'   => 'The "bootstrap" folder is missing or incomplete — re-upload it.',
    'public/build/manifest.json' => 'Run "npm run build" locally and upload the generated "public/build" folder, otherwise styles/scripts will be missing.',
] as $file => $hint) {
    if (! file_exists(__DIR__ . '/' . $file)) {
        $missing[$file] = $hint;
    }
}

if ($missing !== []) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:720px;margin:60px auto;padding:24px;border:1px solid #f39c12;border-radius:8px;background:#fff;">';
    echo '<h1 style="color:#e67e22;margin-top:0;">Deployment files missing</h1><ul>';
    foreach ($missing as $file => $hint) {
        echo '<li><strong>' . htmlspecialchars($file) . '</strong> — ' . htmlspecialchars($hint) . '</li>';
    }
    echo '</ul></div>';
    exit(1);
}

// ---------------------------------------------------------------
// 4. Maintenance mode check.
// ---------------------------------------------------------------
if (file_exists($maintenance = __DIR__ . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// ---------------------------------------------------------------
// 5. Register the Composer autoloader.
// ---------------------------------------------------------------
require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
try {
    /** @var Application $app */
    $app = require_once __DIR__ . '/bootstrap/app.php';

    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    http_response_code(500);

    $message = $e->getMessage();
    $hints = [];

    // Give deployment-specific hints for the most common InfinityFree errors.
    if (str_contains($message, 'SQLSTATE') || str_contains($message, 'Access denied') || str_contains($message, 'connection')) {
        $hints[] = 'Database connection failed. In your .env set DB_HOST, DB_DATABASE, DB_USERNAME and especially DB_PASSWORD exactly as shown in the InfinityFree vPanel "MySQL Databases" page (the password is never empty there).';
        $hints[] = 'Make sure you imported your database tables via phpMyAdmin in the vPanel.';
    }
    if (str_contains($message, 'Permission denied') || str_contains($message, 'failed to open stream')) {
        $hints[] = 'File permission problem. In the InfinityFree file manager set permissions (CHMOD) of the "storage" and "bootstrap/cache" folders to 755.';
    }
    if (str_contains($message, 'Vite manifest')) {
        $hints[] = 'Run "npm run build" on your computer and upload the "public/build" folder it creates.';
    }
    if (str_contains($message, 'No application encryption key')) {
        $hints[] = 'Your .env file is missing or APP_KEY is empty. Upload the .env file from your local project.';
    }

    echo '<div style="font-family:sans-serif;max-width:860px;margin:40px auto;padding:24px;border-left:4px solid #e74c3c;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);">';
    echo '<h1 style="color:#e74c3c;margin-top:0;">Application Error</h1>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' : ' . $e->getLine() . '</p>';
    if ($hints !== []) {
        echo '<h3 style="color:#27ae60;">How to fix this:</h3><ul>';
        foreach ($hints as $hint) {
            echo '<li>' . htmlspecialchars($hint) . '</li>';
        }
        echo '</ul>';
    }
    echo '<details style="margin-top:16px;"><summary style="cursor:pointer;color:#666;">Stack trace (for debugging)</summary>';
    echo '<pre style="background:#f7f7f7;padding:12px;overflow-x:auto;font-size:12px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre></details>';
    echo '</div>';
    exit(1);
}
