<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Support\ExecutionMiddleware\UnusedVariablesMiddleware;

class UnusedVariablesTest extends TestCase
{
    public function testFeatureNotEnabledUnusedVariableIsIgnored(): void
    {
        $this->app['config']->set('graphql.execution_middleware', []);

        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'variables' => [
                'index' => 1,
                'unused_variable' => 'value',
                'another_unused_variable' => 'value',
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => 'Example 2',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $content);
    }

    public function testFeatureEnabledUnusedVariableThrowsError(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            UnusedVariablesMiddleware::class,
        ]);

        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'variables' => [
                'index' => 1,
                'unused_variable' => 'value',
                'another_unused_variable' => 'value',
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);

        $expected = [
            'errors' => [
                [
                    'message' => 'The following variables were provided but not consumed: unused_variable, another_unused_variable',
                    'extensions' => [
                        'category' => 'graphql',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $content);
    }
}
