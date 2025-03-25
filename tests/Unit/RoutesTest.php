<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Rebing\GraphQL\GraphQLHttpMiddleware;
use Rebing\GraphQL\Tests\Support\Objects\ExampleMiddleware;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchema;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchemaWithMethod;
use Rebing\GraphQL\Tests\TestCase;

class RoutesTest extends TestCase
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
                'custom' => [
                    'middleware' => [ExampleMiddleware::class],
                ],
                'with_methods' => [
                    'method' => ['POST'],
                    'middleware' => [ExampleMiddleware::class],
                ],
                'class_based' => ExampleSchema::class,
                'class_based_with_methods' => ExampleSchemaWithMethod::class,
            ],
        ]);
    }

    public function testRoutes(): void
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
                    GraphQLHttpMiddleware::class . ':default',
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.default' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/default',
                'middleware' => [
                    GraphQLHttpMiddleware::class . ':default',
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.custom' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/custom',
                'middleware' => [
                    GraphQLHttpMiddleware::class . ':custom',
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.with_methods' => [
                'methods' => [
                    'POST',
                ],
                'uri' => 'graphql_test/with_methods',
                'middleware' => [
                    GraphQLHttpMiddleware::class . ':with_methods',
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.class_based' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/class_based',
                'middleware' => [
                    GraphQLHttpMiddleware::class . ':class_based',
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.class_based_with_methods' => [
                'methods' => [
                    'POST',
                ],
                'uri' => 'graphql_test/class_based_with_methods',
                'middleware' => [
                    GraphQLHttpMiddleware::class . ':class_based_with_methods',
                    ExampleMiddleware::class,
                ],
            ],
        ];

        $actual = Collection::make(
            app('router')->getRoutes()->getRoutesByName()
        )->map(function (Route $route) {
            return [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'middleware' => $route->middleware(),
            ];
        })->all();

        self::assertEquals($expected, $actual);
    }
}
