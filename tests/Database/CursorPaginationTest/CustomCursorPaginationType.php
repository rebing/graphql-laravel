<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\CursorPaginationTest;

use Rebing\GraphQL\Support\CursorPaginationType;

/**
 * Custom subclass used to verify the `graphql.cursor_pagination_type`
 * config override is honored by `GraphQL::cursorPaginate()`.
 */
class CustomCursorPaginationType extends CursorPaginationType
{
}
