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

        // To still properly support dual tests, we thus have to add this
        if ('0' === env('TESTS_ENABLE_LAZYLOAD_TYPES')) {
            $app['config']->set('graphql.lazyload_types', false);
        }
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
