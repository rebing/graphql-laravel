<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TypesInSchemas;

use Rebing\GraphQL\Tests\TestCase;

class QueriesAndTypesEachInTheirOwnSchemaTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
            'types' => [
                SchemaOne\Type::class,
            ],
        ]);
        $app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaTwo\Query::class,
            ],
            'types' => [
                SchemaTwo\Type::class,
            ],
        ]);
    }

    public function testQueriesAndTypesEachInTheirOwnSchema(): void
    {
        $query = <<<'GRAPHQL'
{
    query {
        name
    }
}
GRAPHQL;

        $actual = $this->httpGraphql($query);

        $expected = [
            'data' => [
                'query' => [
                    'name' => 'example from schema one',
                ],
            ],
        ];
        self::assertSame($expected, $actual);

        $query = <<<'GRAPHQL'
{
    query {
        title
    }
}
GRAPHQL;
        $actual = $this->httpGraphql($query, [
            'schemaName' => 'custom',
        ]);

        $expected = [
            'data' => [
                'query' => [
                    'title' => 'example from schema two',
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }
}
