<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\PaginationTypeTest;

use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\PaginationType;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;
use stdClass;

/**
 * Covers `Rebing\GraphQL\Support\PaginationType` end-to-end.
 *
 * Other tests verify the type is registered (e.g. `GraphQLTest::testGetType...`),
 * but none asserts the resolved response shape. This test runs a real query
 * through `httpGraphql()` and verifies every field defined by `PaginationType`.
 */
class PaginationTypeTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'pagedExamples' => CorrectExamplesPaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
        ]);

        $app['config']->set('app.debug', true);
    }

    public function testQueryReturnsExpectedPaginationShape(): void
    {
        $query = <<<'GRAPHQL'
query ($take: Int!, $page: Int!) {
  pagedExamples(take: $take, page: $page) {
    data { test }
    total
    per_page
    current_page
    from
    to
    last_page
    has_more_pages
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'page' => 1],
        ]);

        // 3 fixture entries, take=2 page=1 -> 2 items, more pages available.
        self::assertSame([
            'data' => [
                'pagedExamples' => [
                    'data' => [
                        ['test' => 'Example 1'],
                        ['test' => 'Example 2'],
                    ],
                    'total' => 3,
                    'per_page' => 2,
                    'current_page' => 1,
                    'from' => 1,
                    'to' => 2,
                    'last_page' => 2,
                    'has_more_pages' => true,
                ],
            ],
        ], $result);
    }

    public function testHasMorePagesAndLastPageOnFinalPage(): void
    {
        $query = <<<'GRAPHQL'
query ($take: Int!, $page: Int!) {
  pagedExamples(take: $take, page: $page) {
    data { test }
    total
    per_page
    current_page
    from
    to
    last_page
    has_more_pages
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'page' => 2],
        ]);

        self::assertSame([
            'data' => [
                'pagedExamples' => [
                    'data' => [
                        ['test' => 'Example 3'],
                    ],
                    'total' => 3,
                    'per_page' => 2,
                    'current_page' => 2,
                    'from' => 3,
                    'to' => 3,
                    'last_page' => 2,
                    'has_more_pages' => false,
                ],
            ],
        ], $result);
    }

    public function testPaginateReturnsCachedInstance(): void
    {
        $first = GraphQL::paginate('Example');
        $second = GraphQL::paginate('Example');

        self::assertSame($first, $second);
        self::assertInstanceOf(PaginationType::class, $first);
        self::assertSame('ExamplePagination', $first->name());
    }

    public function testPaginateRespectsCustomName(): void
    {
        $type = GraphQL::paginate('Example', 'CustomPaginationName');

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertSame('CustomPaginationName', $type->name());
        self::assertSame($type, GraphQL::paginate('Example', 'CustomPaginationName'));
    }

    public function testPaginationPreservesUnderlyingModel(): void
    {
        $underlyingType = new class extends GraphQLType {
            protected $attributes = [
                'name' => 'ModeledExample',
                'model' => stdClass::class,
            ];
        };
        GraphQL::addType($underlyingType);

        $type = GraphQL::paginate('ModeledExample', 'ModeledExamplePagination');

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertSame(stdClass::class, $type->config['model']);
    }

    public function testPaginationFieldsHaveExpectedShape(): void
    {
        $type = GraphQL::paginate('Example');
        self::assertInstanceOf(ObjectType::class, $type);

        $fields = $type->getFields();

        $expected = [
            'data',
            'total',
            'per_page',
            'current_page',
            'from',
            'to',
            'last_page',
            'has_more_pages',
        ];

        foreach ($expected as $name) {
            self::assertArrayHasKey($name, $fields);
        }
    }
}
