<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TypesInSchemas;

use Rebing\GraphQL\Tests\TestCase;

class TypesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Note: deliberately not calling parent to start with a clean config

        // To still properly support dual tests, we thus have to add this
        if ('0' === env('TESTS_ENABLE_LAZYLOAD_TYPES')) {
            $app['config']->set('graphql.lazyload_types', false);
        }
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
                    'extensions' => [
                        'category' => 'graphql',
                    ],
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

        $actual = $this->graphql($query, [
            'opts' => [
                'schema' => 'custom',
            ],
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

        $actual = $this->graphql($query, [
            'opts' => [
                'schema' => 'custom',
            ],
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

    public function testQueriesAndTypesEachInTheirOwnSchema(): void
    {
        $this->app['config']->set('graphql.schemas.default', [
            'query' => [
                SchemaOne\Query::class,
            ],
            'types' => [
                SchemaOne\Type::class,
            ],
        ]);
        $this->app['config']->set('graphql.schemas.custom', [
            'query' => [
                SchemaTwo\Query::class,
            ],
            'types' => [
                SchemaTwo\Type::class,
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

        $query = <<<'GRAPHQL'
{
    query {
        title
    }
}
GRAPHQL;
        $actual = $this->graphql($query, [
            'opts' => [
                'schema' => 'custom',
            ],
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
        name
    }
}
GRAPHQL;
        $actual = $this->graphql($query, [
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
                    'extensions' => [
                        'category' => 'graphql',
                    ],
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
