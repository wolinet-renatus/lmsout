<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// When Apache rewrites requests from the project root into /public without
// exposing "/public" in REQUEST_URI, normalize SCRIPT_NAME so Laravel resolves
// the active subdirectory and route paths automatically across any deployment.
if (
    isset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI']) &&
    preg_match('#^(.*)/public/index\.php$#', $_SERVER['SCRIPT_NAME'], $matches)
) {
    $appPrefix = $matches[1];
    $publicPrefix = $appPrefix . '/public';
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

    $isPublicRequest = $requestPath === $publicPrefix
        || str_starts_with($requestPath, $publicPrefix . '/');

    if (!$isPublicRequest && ($appPrefix === '' || $requestPath === $appPrefix || str_starts_with($requestPath, $appPrefix . '/'))) {
        $_SERVER['SCRIPT_NAME'] = $appPrefix . '/index.php';
        $_SERVER['PHP_SELF'] = $appPrefix . '/index.php';
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
