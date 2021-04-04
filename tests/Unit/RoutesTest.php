<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Utils\BuildSchema;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Tests\Support\Objects\ExampleMiddleware;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchema;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchemaWithMethod;
use Rebing\GraphQL\Tests\TestCase;

class RoutesTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql', [

            'prefix' => 'graphql_test',

            'graphiql' => [
                'display' => false,
            ],

            'schemas' => [
                'default' => [
                    'middleware' => [ExampleMiddleware::class]
                ],
                'custom' => [
                    'middleware' => [ExampleMiddleware::class]
                ],
                'with_methods' => [
                    'method' => ['post'],
                    'middleware' => [ExampleMiddleware::class]
                ],
                'class_based' => ExampleSchema::class,
                'class_based_with_methods' => ExampleSchemaWithMethod::class,
                'shorthand' => BuildSchema::build('
                    schema {
                        query: ShorthandExample
                    }

                    type ShorthandExample {
                        echo(message: String!): String!
                    }
                '),
            ]

        ]);
    }

    public function testRoutes(): void
    {
        $expected = [
            'graphql.query' => [
                'methods' => ['GET', 'HEAD'],
                'uri' => 'graphql_test',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.query.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.default' => [
                'methods' => ['GET', 'HEAD'],
                'uri' => 'graphql_test/{default}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.default.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{default}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.custom' => [
                'methods' => ['GET', 'HEAD'],
                'uri' => 'graphql_test/{custom}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.custom.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{custom}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.with_methods.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{with_methods}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.class_based' => [
                'methods' => ['GET', 'HEAD'],
                'uri' => 'graphql_test/{class_based}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.class_based.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{class_based}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.class_based_with_methods.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{class_based_with_methods}',
                'middleware' => [ExampleMiddleware::class],
            ],
            'graphql.shorthand' => [
                'methods' => ['GET', 'HEAD'],
                'uri' => 'graphql_test/{shorthand}',
                'middleware' => [],
            ],
            'graphql.shorthand.post' => [
                'methods' => ['POST'],
                'uri' => 'graphql_test/{shorthand}',
                'middleware' => [],
            ],
        ];

        $this->assertEquals($expected, Collection::make(
            app('router')->getRoutes()->getRoutesByName()
        )->map(function (Route $route) {
            return [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'middleware' => $route->middleware(),
            ];
        })->all());
    }
}
