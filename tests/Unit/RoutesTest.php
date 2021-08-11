<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Tests\Support\Objects\ExampleMiddleware;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchema;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchemaWithMethod;
use Rebing\GraphQL\Tests\TestCase;

class RoutesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql', [
            'route' => [
                'prefix' => 'graphql_test',
            ],

            'graphiql' => [
                'display' => false,
            ],

            'schemas' => [
                'default' => [
                    'middleware' => [ExampleMiddleware::class],
                ],
                'custom' => [
                    'middleware' => [ExampleMiddleware::class],
                ],
                'with_methods' => [
                    'method' => ['post'],
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
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.with_methods' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/with_methods',
                'middleware' => [
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
                    ExampleMiddleware::class,
                ],
            ],
            'graphql.class_based_with_methods' => [
                'methods' => [
                    'GET',
                    'POST',
                    'HEAD',
                ],
                'uri' => 'graphql_test/class_based_with_methods',
                'middleware' => [
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
