<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\TestCase;

class SchemaEncodedPathTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.custom', [
            'query' => [
                'examples' => ExamplesQuery::class,
            ],
        ]);
    }

    public function testPercentEncodedSchemaSegmentResolvesToTheMatchedSchema(): void
    {
        $graphql = <<<'GRAPHQL'
{
    examples {
        test
    }
}
GRAPHQL;

        $response = $this->call('POST', '/graphql/%63ustom', [
            'query' => $graphql,
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'data' => [
                'examples' => [
                    ['test' => 'Example 1'],
                    ['test' => 'Example 2'],
                    ['test' => 'Example 3'],
                ],
            ],
        ], $response->json());
    }
}
