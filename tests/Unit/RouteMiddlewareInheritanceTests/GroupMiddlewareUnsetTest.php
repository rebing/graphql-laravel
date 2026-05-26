<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\RouteMiddlewareInheritanceTests;

use Illuminate\Routing\Route;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies route action middleware when `route.middleware` is NOT set
 * globally.
 */
class GroupMiddlewareUnsetTest extends TestCase
{
    protected const SCHEMA_MIDDLEWARE = 'auth:api';

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('filesystems.disks.local.serve', false);

        $app['config']->set('graphql', [
            'route' => [
                'prefix' => 'graphql_test',
                // No `middleware` key — falsy default.
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

    public function testOnlySchemaMiddlewareApplies(): void
    {
        self::assertSame(
            [self::SCHEMA_MIDDLEWARE],
            $this->routeMiddleware('graphql.with_schema_mw'),
        );
    }

    public function testNoMiddlewareWhenNothingConfigured(): void
    {
        self::assertSame(
            [],
            $this->routeMiddleware('graphql.without_schema_mw'),
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
