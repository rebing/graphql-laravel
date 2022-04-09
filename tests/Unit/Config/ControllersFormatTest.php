<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Config;

use Rebing\GraphQL\GraphQLController;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;

class ControllersFormatTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql', [
            'route' => [
                'controller' => [GraphQLController::class, 'query'],
            ],

            'schemas' => [
                'default' => [
                    'query' => [
                        'examples' => ExamplesQuery::class,
                    ],
                ],
            ],

            'types' => [
                'Example' => ExampleType::class,
            ],

            'graphiql' => [
                'controller' => [GraphQLController::class, 'graphiql'],
            ],
        ]);
    }

    public function testControllerHasValidMethod(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testGraphiQLHasValidMethod(): void
    {
        $response = $this->call('GET', '/graphiql');

        self::assertSame(200, $response->getStatusCode());
    }
}
