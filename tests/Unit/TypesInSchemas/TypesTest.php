<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TypesInSchemas;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

class TypesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Note: deliberately not calling parent to start with a clean config
    }

    public function testQueryAndTypeInDefaultSchema(): void
    {
        $this->app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
            'types' => [
                SchemaOne\Type::class,
            ],
        ]);

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
    }

    public function testQueryInDefaultSchemaAndTypeGlobal(): void
    {
        $this->app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
        ]);
        $this->app['config']->set('graphql.types', [
            SchemaOne\Type::class,
        ]);

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
    }

    public function testQueryAndTypeInCustomSchemaQueryingDefaultSchema(): void
    {
        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaOne\Query::class,
            ],
            'types' => [
                SchemaOne\Type::class,
            ],
        ]);

        $query = <<<'GRAPHQL'
{
    query {
        name
    }
}
GRAPHQL;

        $actual = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'Cannot query field "query" on type "Query".',
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 5,
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }

    public function testQueryAndTypeInCustomSchemaQueryingCustomSchema(): void
    {
        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaOne\Query::class,
            ],
            'types' => [
                SchemaOne\Type::class,
            ],
        ]);

        $query = <<<'GRAPHQL'
{
    query {
        name
    }
}
GRAPHQL;

        $actual = GraphQL::query($query, null, [
            'schema' => 'custom',
        ]);

        $expected = [
            'data' => [
                'query' => [
                    'name' => 'example from schema one',
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }

    public function testQueryInCustomSchemaAndTypeGlobalQueryingNonDefaultSchema(): void
    {
        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaOne\Query::class,
            ],
        ]);
        $this->app['config']->set('graphql.types', [
            SchemaOne\Type::class,
        ]);

        $query = <<<'GRAPHQL'
{
    query {
        name
    }
}
GRAPHQL;

        $actual = GraphQL::query($query, null, [
            'schema' => 'custom',
        ]);

        $expected = [
            'data' => [
                'query' => [
                    'name' => 'example from schema one',
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }

    public function testSameQueryInDifferentSchemasAndTypeGlobal(): void
    {
        $this->app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
        ]);

        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaOne\Query::class,
            ],
        ]);

        $this->app['config']->set('graphql.types', [
            SchemaOne\Type::class,
        ]);

        $query = <<<'GRAPHQL'
{
    query {
        name
    }
}
GRAPHQL;

        $expected = [
            'data' => [
                'query' => [
                    'name' => 'example from schema one',
                ],
            ],
        ];

        $actual = $this->httpGraphql($query);

        self::assertSame($expected, $actual);

        $actual = GraphQL::query($query, null, [
            'schema' => 'custom',
        ]);

        self::assertSame($expected, $actual);
    }

    public function testDifferentQueriesInDifferentSchemasAndTypeGlobal(): void
    {
        $this->app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
        ]);
        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaTwo\Query::class,
            ],
        ]);
        $this->app['config']->set('graphql.types', [
            SchemaOne\Type::class,
        ]);

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
            'expectErrors' => true,
            'schema' => 'custom',
        ]);
        $expected = [
            'errors' => [
                [
                    'message' => 'Cannot query field "title" on type "Type".',
                    'locations' => [
                        [
                            'line' => 3,
                            'column' => 9,
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }
}
