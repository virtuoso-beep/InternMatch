<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.mysql.database');

        if ($connection !== 'mysql' || ! is_string($database) || ! str_ends_with($database, '_testing')
            || $app['config']->get('database.connections.mysql.url')
            || ! filter_var(env('ALLOW_TEST_DATABASE_RESET', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException('Tests require MySQL, a dedicated *_testing database, no DB_URL, and ALLOW_TEST_DATABASE_RESET=true in .env.testing.');
        }

        return $app;
    }
}
