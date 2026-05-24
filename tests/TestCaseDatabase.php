<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests;

use Illuminate\Foundation\Application;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

abstract class TestCaseDatabase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/Support/database/migrations');

        // This takes care of refreshing the database between tests
        // as we are using the in-memory SQLite db we do not need RefreshDatabase
        $this->artisan('migrate');
    }

    protected function setUpTraits(): array
    {
        $uses = parent::setUpTraits();

        if (isset($uses[SqlAssertionTrait::class])) {
            $this->setupTraitForSqlAssertion(); // @phpstan-ignore method.notFound
        }

        return $uses;
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Whether the running Laravel framework version emits a quoted
     * `as "aggregate"` alias in aggregate SQL queries (e.g. count(*)
     * from the `unique` validation rule).
     *
     * Introduced in Laravel 13.10.0 by laravel/framework#60140
     * ("Delimit aggregate alias"). Older versions emit `as aggregate`.
     */
    protected static function quotesAggregateAlias(): bool
    {
        return version_compare(Application::VERSION, '13.10.0', '>=');
    }
}
