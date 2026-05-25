<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\CursorPaginationTest;

use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\CursorPaginationType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\TestCaseDatabase;

/**
 * Covers `Rebing\GraphQL\Support\CursorPaginationType` and the
 * `Rebing\GraphQL\GraphQL::cursorPaginate()` helper.
 *
 * Cursor pagination requires a real DB-backed query so that the underlying
 * Eloquent builder can compute next/previous cursors; otherwise these are
 * always `null`.
 */
class CursorPaginationTest extends TestCaseDatabase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'usersCursorPagination' => UsersCursorPaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            UserType::class,
        ]);
    }

    public function testFirstPageReturnsDataAndNextCursor(): void
    {
        // Stable, deterministic seed
        User::factory()->createMany([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Carol'],
        ]);

        $query = <<<'GRAPHQL'
query ($take: Int!) {
  usersCursorPagination(take: $take) {
    data { id name }
    per_page
    previous_cursor
    next_cursor
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'variables' => ['take' => 2],
        ]);

        $payload = $result['data']['usersCursorPagination'];

        self::assertSame(2, $payload['per_page']);
        self::assertCount(2, $payload['data']);
        self::assertSame('Alice', $payload['data'][0]['name']);
        self::assertSame('Bob', $payload['data'][1]['name']);

        self::assertNull($payload['previous_cursor']);
        self::assertNotNull($payload['next_cursor']);
        self::assertIsString($payload['next_cursor']);
    }

    public function testNextCursorReturnsRemainingPage(): void
    {
        User::factory()->createMany([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Carol'],
        ]);

        $query = <<<'GRAPHQL'
query ($take: Int!, $cursor: String) {
  usersCursorPagination(take: $take, cursor: $cursor) {
    data { id name }
    per_page
    previous_cursor
    next_cursor
  }
}
GRAPHQL;

        // First page to get a cursor
        $first = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'cursor' => null],
        ]);

        $cursor = $first['data']['usersCursorPagination']['next_cursor'];
        self::assertNotNull($cursor);

        // Second page using the cursor
        $second = $this->httpGraphql($query, [
            'variables' => ['take' => 2, 'cursor' => $cursor],
        ]);

        $payload = $second['data']['usersCursorPagination'];

        self::assertCount(1, $payload['data']);
        self::assertSame('Carol', $payload['data'][0]['name']);

        // No more next cursor on the last page; previous cursor lets us go back.
        self::assertNull($payload['next_cursor']);
        self::assertNotNull($payload['previous_cursor']);
    }

    public function testCursorPaginateReturnsCachedInstance(): void
    {
        $first = GraphQL::cursorPaginate('User');
        $second = GraphQL::cursorPaginate('User');

        self::assertSame($first, $second);
        self::assertInstanceOf(CursorPaginationType::class, $first);
        self::assertSame('UserCursorPagination', $first->name());
    }

    public function testCursorPaginateRespectsCustomName(): void
    {
        $type = GraphQL::cursorPaginate('User', 'CustomCursorPaginationName');

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertSame('CustomCursorPaginationName', $type->name());
        self::assertSame($type, GraphQL::cursorPaginate('User', 'CustomCursorPaginationName'));
    }

    public function testCursorPaginationFieldsHaveExpectedShape(): void
    {
        $type = GraphQL::cursorPaginate('User');
        self::assertInstanceOf(ObjectType::class, $type);

        $fields = $type->getFields();

        self::assertArrayHasKey('data', $fields);
        self::assertArrayHasKey('per_page', $fields);
        self::assertArrayHasKey('previous_cursor', $fields);
        self::assertArrayHasKey('next_cursor', $fields);

        // Cursor pagination intentionally omits offset-based fields.
        self::assertArrayNotHasKey('total', $fields);
        self::assertArrayNotHasKey('current_page', $fields);
        self::assertArrayNotHasKey('last_page', $fields);
        self::assertArrayNotHasKey('has_more_pages', $fields);
    }

    public function testCustomCursorPaginationTypeConfigIsHonored(): void
    {
        $this->app['config']->set('graphql.cursor_pagination_type', CustomCursorPaginationType::class);

        $type = GraphQL::cursorPaginate('User', 'UserCursorPaginationCustomConfig');

        self::assertInstanceOf(CustomCursorPaginationType::class, $type);
    }
}
