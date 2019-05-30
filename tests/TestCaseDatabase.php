<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests;

abstract class TestCaseDatabase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Support/database/migrations');
        $this->withFactories(__DIR__.'/Support/database/factories');

        // This takes care of refreshing the database between tests
        // as we are using the in-memory SQLite db we do not need RefreshDatabase
        $this->artisan('migrate');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
