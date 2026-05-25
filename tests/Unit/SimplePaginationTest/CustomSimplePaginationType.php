<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SimplePaginationTest;

use Rebing\GraphQL\Support\SimplePaginationType;

/**
 * Custom subclass used to verify the `graphql.simple_pagination_type`
 * config override is honored by `GraphQL::simplePaginate()`.
 */
class CustomSimplePaginationType extends SimplePaginationType
{
}
