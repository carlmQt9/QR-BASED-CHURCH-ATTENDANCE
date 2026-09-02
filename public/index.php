<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Laravel 12 requires PHP 8.2+
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    http_response_code(500);
    echo '<h1>PHP version too old</h1><p>This application requires PHP 8.2+. Server is running PHP ' . PHP_VERSION . '.</p>';
    exit(1);
}

// Ensure required writable directories exist (shared-hosting uploads often miss them)
foreach ([
    __DIR__.'/../storage/framework/views',
    __DIR__.'/../storage/framework/sessions',
    __DIR__.'/../storage/framework/cache/data',
    __DIR__.'/../storage/logs',
    __DIR__.'/../bootstrap/cache',
] as $requiredDir) {
    if (! is_dir($requiredDir)) {
        @mkdir($requiredDir, 0755, true);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

