<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SimplePaginationTest;

use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\SimplePaginationType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Covers `Rebing\GraphQL\Support\SimplePaginationType` and the
 * `Rebing\GraphQL\GraphQL::simplePaginate()` helper. Both were entirely
 * uncovered.
 */
class SimplePaginationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'examplesSimplePagination' => ExamplesSimplePaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
        ]);

        $app['config']->set('app.debug', true);
    }

    public function testQueryReturnsExpectedSimplePaginationShape(): void
    {
        $query = <<<'GRAPHQL'
query ($take: Int!, $page: Int!) {
  examplesSimplePagination(take: $take, page: $page) {
    data { test }
    per_page
    current_page
    from
    to
    has_more_pages
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'page' => 1],
        ]);

        self::assertSame([
            'data' => [
                'examplesSimplePagination' => [
                    'data' => [
                        ['test' => 'Example 1'],
                        ['test' => 'Example 2'],
                    ],
                    'per_page' => 2,
                    'current_page' => 1,
                    'from' => 1,
                    'to' => 2,
                    'has_more_pages' => true,
                ],
            ],
        ], $result);
    }

    public function testHasMorePagesIsFalseOnLastPage(): void
    {
        $query = <<<'GRAPHQL'
query ($take: Int!, $page: Int!) {
  examplesSimplePagination(take: $take, page: $page) {
    data { test }
    per_page
    current_page
    from
    to
    has_more_pages
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'page' => 2],
        ]);

        self::assertSame([
            'data' => [
                'examplesSimplePagination' => [
                    'data' => [
                        ['test' => 'Example 3'],
                    ],
                    'per_page' => 2,
                    'current_page' => 2,
                    'from' => 3,
                    'to' => 3,
                    'has_more_pages' => false,
                ],
            ],
        ], $result);
    }

    public function testSimplePaginateReturnsCachedInstance(): void
    {
        $first = GraphQL::simplePaginate('Example');
        $second = GraphQL::simplePaginate('Example');

        self::assertSame($first, $second);
        self::assertInstanceOf(SimplePaginationType::class, $first);
        self::assertSame('ExampleSimplePagination', $first->name());
    }

    public function testSimplePaginateRespectsCustomName(): void
    {
        $type = GraphQL::simplePaginate('Example', 'CustomSimplePaginationName');

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertSame('CustomSimplePaginationName', $type->name());
        // Subsequent call returns the same cached instance keyed by custom name.
        self::assertSame($type, GraphQL::simplePaginate('Example', 'CustomSimplePaginationName'));
    }

    public function testSimplePaginationFieldsHaveExpectedShape(): void
    {
        $type = GraphQL::simplePaginate('Example');
        self::assertInstanceOf(ObjectType::class, $type);

        $fields = $type->getFields();

        self::assertArrayHasKey('data', $fields);
        self::assertArrayHasKey('per_page', $fields);
        self::assertArrayHasKey('current_page', $fields);
        self::assertArrayHasKey('from', $fields);
        self::assertArrayHasKey('to', $fields);
        self::assertArrayHasKey('has_more_pages', $fields);

        // Simple pagination intentionally omits `total` and `last_page`
        // (that's what distinguishes it from full pagination).
        self::assertArrayNotHasKey('total', $fields);
        self::assertArrayNotHasKey('last_page', $fields);
    }

    public function testCustomSimplePaginationTypeConfigIsHonored(): void
    {
        $this->app['config']->set('graphql.simple_pagination_type', CustomSimplePaginationType::class);

        // Use a fresh custom name so the cache from earlier tests doesn't interfere.
        $type = GraphQL::simplePaginate('Example', 'ExampleSimplePaginationCustomConfig');

        self::assertInstanceOf(CustomSimplePaginationType::class, $type);
    }
}
