<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NoOpMiddlewareTest;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies that a `Rebing\GraphQL\Support\Middleware` subclass which does NOT
 * override `handle()` still works (the base class's default `handle()` simply
 * passes through to `$next(...)`).
 *
 * Existing middleware tests all use middleware with custom `handle()` methods,
 * leaving the parent's default implementation uncovered.
 */
class NoOpMiddlewareTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                GreetingQuery::class,
            ],
        ]);

        CountingNoOpMiddleware::$resolveCalls = 0;
    }

    public function testNoOpMiddlewareDoesNotInterfereWithResolver(): void
    {
        $result = GraphQL::queryAndReturnResult('{ greet(name: "World") }');

        self::assertEmpty($result->errors);
        self::assertSame(['greet' => 'Hello, World!'], $result->data);

        // Sanity-check: middleware really ran (otherwise the test would be vacuous).
        self::assertSame(1, CountingNoOpMiddleware::$resolveCalls);
    }
}
