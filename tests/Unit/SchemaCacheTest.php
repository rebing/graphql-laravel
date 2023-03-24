<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\Contracts\ConfigConvertible;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SchemaCache\SchemaCache;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesPaginationQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
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
        foreach (self::provideSchemaNames() as [$schemaName]) {
            $this->schemaCache->flush($schemaName);
        }

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.cache', true);

        $app['config']->set('graphql.schemas.default.query.notInstantiated', NotInstantiatedQuery::class);
        $app['config']->set('graphql.schemas.default.query.returnScalar', ReturnScalarQuery::class);
        $app['config']->set('graphql.schemas.default.query.examplesPagination', ExamplesPaginationQuery::class);

        $app['config']->set('graphql.schemas.default.types.TestScalar', TestScalar::class);

        $app['config']->set('graphql.schemas.class_based', ExampleSchema::class);
    }

    /**
     * @return array<array<string>>
     */
    public static function provideSchemaNames(): array
    {
        return [
            ['default'],
            ['class_based'],
        ];
    }

    /**
     * @dataProvider provideSchemaNames
     */
    public function testCachingSchema(string $schemaName): void
    {
        self::assertTrue($this->schemaCache->enabled($schemaName));

        $this->schemaCache->set($schemaName, $schema = GraphQL::schema($schemaName));

        $schemaFromCache = $this->schemaCache->get($schemaName);

        self::assertInstanceOf(Schema::class, $schemaFromCache);

        // Assert the cached schema contains exactly the same types
        self::assertSame(array_keys($schema->getTypeMap()), array_keys($schemaFromCache->getTypeMap()));
    }

    public function testUnrelatedTypeIsNotInstantiated(): void
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

    public function testSchemaCacheWithScalar(): void
    {
        $this->schemaCache->set('default', GraphQL::schema());

        GraphQL::clearSchema('default');

        $result = $this->call('GET', '/graphql', [
            'query' => '{ returnScalar }',
        ])->assertOk()->json();

        $expected = [
            'data' => [
                'returnScalar' => 'JUST A STRING',
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testSchemaCacheWithPaginateType(): void
    {
        $this->schemaCache->set('default', GraphQL::schema());

        GraphQL::clearSchema('default');

        $result = $this->call('GET', '/graphql', [
            'query' => '{ examplesPagination(take: 3, page: 1) { data { test } total } }',
        ])->assertOk()->json();

        $expected = [
            'data' => [
                'examplesPagination' => [
                    'data' => [
                        ['test' => 'Example 1'],
                        ['test' => 'Example 2'],
                        ['test' => 'Example 3'],
                    ],
                    'total' => 3,
                ],
            ],
        ];
        self::assertSame($expected, $result);
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

class ExampleSchema implements ConfigConvertible
{
    public function toConfig(): array
    {
        return [
            'query' => [
                'examples' => ExamplesQuery::class,
            ],
            'cache' => true,
        ];
    }
}

class TestScalar extends ScalarType implements TypeConvertible
{
    public function serialize($value)
    {
        return strtoupper($value);
    }

    public function parseValue($value)
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new InvariantViolation('Expected node of type ' . StringValueNode::class . ' , got ' . \get_class($valueNode));
        }

        return $valueNode->value;
    }

    public function toType(): Type
    {
        return new self();
    }
}

class ReturnScalarQuery extends Query
{
    protected $attributes = [
        'name' => 'returnScalar',
    ];

    public function type(): Type
    {
        return GraphQL::type('TestScalar');
    }

    public function resolve(): string
    {
        return 'just a string';
    }
}
