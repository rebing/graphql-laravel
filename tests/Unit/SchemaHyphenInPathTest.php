<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SchemaHyphenInPathTest extends TestCase
{
    public function testWithHyphen(): void
    {
        $graphql = <<<'GRAPHQL'
{
    examples {
        test
    }
}
GRAPHQL;

        $response = $this->call('GET', '/graphql/with-hyphen', [
            'query' => $graphql,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $result = $response->json();

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
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.with-hyphen', [
            'query' => [
                'examples' => ExamplesQuery::class,
            ],
        ]);
    }
}
