<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class SchemaEncodedPathTest extends TestCase
{
    public function testPercentEncodedSchemaSegmentResolvesToTheMatchedSchema(): void
    {
        $graphql = <<<'GRAPHQL'
{
    examplesCustom {
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
                'examplesCustom' => [
                    ['test' => 'Example 1'],
                    ['test' => 'Example 2'],
                    ['test' => 'Example 3'],
                ],
            ],
        ], $response->json());
    }
}
