<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Utils\BuildSchema;
use Illuminate\Routing\Router;
use Rebing\GraphQL\Tests\Support\Objects\ExampleMiddleware;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchema;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchemaWithMethod;
use Rebing\GraphQL\Tests\TestCase;

class NoRoutesRegisteredTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql', [
            'route' => [],
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
                'shorthand' => BuildSchema::build('
                    schema {
                        query: ShorthandExample
                    }

                    type ShorthandExample {
                        echo(message: String!): String!
                    }
                '),
            ],
        ]);
    }

    public function testNoRoutesAreRegistered(): void
    {
        /** @var Router $router */
        $router = app('router');

        self::assertCount(0, $router->getRoutes()->getRoutes());
    }
}
