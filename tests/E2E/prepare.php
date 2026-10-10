<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Contracts\Console\Kernel;

// Invoked only by Playwright's isolated webServer environment, never from a route.
require __DIR__.'/../../vendor/autoload.php';

try {
    $storage = realpath(__DIR__.'/../../storage').'/e2e';
    if (getenv('LARAVEL_STORAGE_PATH') !== $storage || ! preg_match('/^[a-z][a-z0-9_]*_e2e$/', getenv('DB_DATABASE') ?: '')
        || getenv('DB_CONNECTION') !== 'pgsql' || getenv('DB_URL') !== '') {
        throw new RuntimeException('Refusing E2E reset without a dedicated *_e2e PostgreSQL database and isolated storage.');
    }
    foreach (['app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
        if (! is_dir($storage.'/'.$directory)) {
            mkdir($storage.'/'.$directory, 0700, true);
        }
    }
    $app = require __DIR__.'/../../bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    if ($app->configurationIsCached() || ! $app->environment('local')
        || $app['db']->connection()->getDatabaseName() !== getenv('DB_DATABASE')
        || $app->storagePath() !== $storage) {
        throw new RuntimeException('E2E requires uncached local config, isolated storage and built assets.');
    }
    foreach ([['migrate:fresh', ['--force' => true, '--no-interaction' => true]], ['db:seed', ['--class' => DemoSeeder::class, '--force' => true, '--no-interaction' => true]]] as [$command, $options]) {
        if ($kernel->call($command, $options) !== 0) {
            throw new RuntimeException('E2E database setup failed: '.$command);
        }
    }
    echo "Prepared dedicated E2E database and demo accounts.\n";

} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
