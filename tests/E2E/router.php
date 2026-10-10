<?php

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Built-asset preview for E2E: ignore the developer's Vite hot file.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__.'/../../public'.rawurldecode($path));
$public = realpath(__DIR__.'/../../public');
if ($path !== '/' && $file !== false && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->booted(fn () => $app->make(Vite::class)->useHotFile($app->storagePath('hot')));
$app->handleRequest(Request::capture());
