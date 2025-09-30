<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Tests\Support\Objects\ExampleMiddleware;
use Rebing\GraphQL\Tests\TestCase;

class RouteWithSchemaTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Laravel registers routes for local filesystems by default.
        // However, for the purpose of this test, we don't want it to register any routes.
        $app['config']->set('filesystems.disks.local.serve', false);

        $app['config']->set('graphql', [
            'route' => [
                'prefix' => 'graphql_test',
            ],

            'schemas' => [
                'default' => [
                    'middleware' => [ExampleMiddleware::class],
                ],
                'with_schema_attributes' => [
                    'middleware' => [ExampleMiddleware::class],
                    'schema_attributes' => [
                        'domain' => 'api.example.com',
                    ],
                ],
            ],
        ]);
    }

    public function testRoutesWithSchemaAttributes(): void
    {
        $expected = [
            'graphql' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test',
                'middleware' => [
                    ExampleMiddleware::class,
                ],
                'domain' => null,
                'action_middleware' => [ExampleMiddleware::class],
                'action_excluded_middleware' => [],
            ],
            'graphql.default' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/default',
                'middleware' => [
                    ExampleMiddleware::class,
                ],
                'domain' => null,
                'action_middleware' => [ExampleMiddleware::class],
                'action_excluded_middleware' => [],
            ],
            'graphql.with_schema_attributes' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/with_schema_attributes',
                'middleware' => [
                    ExampleMiddleware::class,
                ],
                'domain' => 'api.example.com',
                'action_middleware' => [ExampleMiddleware::class],
                'action_excluded_middleware' => [],
            ],
        ];

        $actual = Collection::make(
            app('router')->getRoutes()->getRoutesByName()
        )->map(function (Route $route) {
            $action = $route->getAction();
            return [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'middleware' => $route->middleware(),
                'domain' => $route->getDomain(),
                'action_middleware' => $action['middleware'] ?? [],
                'action_excluded_middleware' => $action['excluded_middleware'] ?? [],
            ];
        })->all();

        self::assertEquals($expected, $actual);
    }
}


