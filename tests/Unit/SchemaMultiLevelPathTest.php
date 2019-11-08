<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\TestCase;

class SchemaMultiLevelPathTest extends TestCase
{
    public function testMultiLevelPath(): void
    {
        $graphql = <<<'GRAPHQL'
{
    examples {
        test
    }
}
GRAPHQL;

        $response = $this->call('GET', '/graphql/multi/level', [
            'query' => $graphql,
        ]);

        $expectedResult = [
            'data' => [
                'examples' => [
                    [
                        'test' => 'Example 1',
                    ],
                    [
                        'test' => 'Example 2',
                    ],
                    [
                        'test' => 'Example 3',
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $response->json());
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.multi/level', [
            'query' => [
                'examples' => ExamplesQuery::class,
            ],
        ]);
    }
}
