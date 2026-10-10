<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();
        if (! $app->environment('testing') || $app->configurationIsCached()
            || $connection->getDriverName() !== 'pgsql'
            || ! preg_match('/(?:^test_|_test$|_e2e$)/', $connection->getDatabaseName())) {
            throw new RuntimeException('Tests require APP_ENV=testing, uncached config and a dedicated PostgreSQL database named test_* or *_test (or *_e2e).');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
