<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\RouteMiddlewareInheritanceTests;

use Illuminate\Routing\Route;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies route action middleware when `route.middleware` IS set globally.
 */
class GroupMiddlewareSetTest extends TestCase
{
    protected const GROUP_MIDDLEWARE = 'throttle:60,1';
    protected const SCHEMA_MIDDLEWARE = 'auth:api';

    protected function getEnvironmentSetUp($app): void
    {
        // Disable Laravel's default route registrations so only the GraphQL
        // routes appear in the assertions below.
        $app['config']->set('filesystems.disks.local.serve', false);

        $app['config']->set('graphql', [
            'route' => [
                'prefix' => 'graphql_test',
                'middleware' => [self::GROUP_MIDDLEWARE],
            ],
            'default_schema' => 'with_schema_mw',
            'schemas' => [
                'with_schema_mw' => [
                    'query' => [],
                    'middleware' => [self::SCHEMA_MIDDLEWARE],
                ],
                'without_schema_mw' => [
                    'query' => [],
                ],
            ],
        ]);
    }

    public function testWithSchemaMiddlewareEachAppearsOnce(): void
    {
        // Group middleware then schema middleware, in registration order.
        self::assertSame(
            [self::GROUP_MIDDLEWARE, self::SCHEMA_MIDDLEWARE],
            $this->routeMiddleware('graphql.with_schema_mw'),
        );
    }

    public function testWithoutSchemaMiddlewareGroupAppearsOnce(): void
    {
        // The bug-regression assertion: under the old fallback chain this
        // would be ['throttle:60,1', 'throttle:60,1'].
        self::assertSame(
            [self::GROUP_MIDDLEWARE],
            $this->routeMiddleware('graphql.without_schema_mw'),
        );
    }

    public function testFixAppliesAlsoToTheUnnamedDefaultSchemaRoute(): void
    {
        // The default schema is bound twice: once at `/graphql_test/<name>`
        // (named `graphql.<name>`) and once at the bare prefix (named
        // `graphql`). Both share the same `$actions` array, so the fix
        // must reach the bare-prefix route as well.
        self::assertSame(
            [self::GROUP_MIDDLEWARE, self::SCHEMA_MIDDLEWARE],
            $this->routeMiddleware('graphql'),
        );
    }

    /**
     * @return list<string>
     */
    private function routeMiddleware(string $name): array
    {
        /** @var Route|null $route */
        $route = $this->app['router']->getRoutes()->getByName($name);

        if (null === $route) {
            self::fail("Route '{$name}' was not registered.");
        }

        return array_values($route->middleware());
    }
}
