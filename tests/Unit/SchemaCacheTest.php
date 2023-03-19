<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Rebing\GraphQL\SchemaCache;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\TestCase;

class SchemaCacheTest extends TestCase
{
    private SchemaCache $schemaCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaCache = app(SchemaCache::class);
    }

    protected function tearDown(): void
    {
        $this->schemaCache->flush('default');

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.cache', true);

        $app['config']->set('graphql.schemas.default.query.notInstantiated', NotInstantiatedQuery::class);
    }

    public function testCachingSchema(): void
    {
        $schemaName = 'default';

        self::assertTrue($this->schemaCache->enabled($schemaName));

        $this->schemaCache->set($schemaName, $schema = GraphQL::schema($schemaName));

        $schemaFromCache = $this->schemaCache->get($schemaName);

        self::assertInstanceOf(Schema::class, $schemaFromCache);

        // Assert the cached schema contains exactly the same types
        self::assertSame(array_keys($schema->getTypeMap()), array_keys($schemaFromCache->getTypeMap()));
    }

    public function testResolveWithSchemaCache(): void
    {
        $this->schemaCache->set('default', GraphQL::schema());

        GraphQL::clearSchema('default');

        $otherQueryMock = $this->mock(NotInstantiatedQuery::class);

        $result = $this->call('GET', '/graphql', [
            'query' => '{ examples { test } }',
        ])->assertOk()->json();

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
        self::assertSame($expectedResult, $result);

        $otherQueryMock->shouldNotHaveReceived('toArray');
    }
}

class NotInstantiatedQuery extends Query
{
    protected $attributes = [
        'name' => 'notInstantiated',
    ];

    public function type(): Type
    {
        return GraphQL::type('Example');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ];
    }

    public function resolve(): void
    {
    }
}
