<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Contracts;

/**
 * Marker interface for wrapper types (pagination, custom wrappers via
 * GraphQL::wrapType()).
 *
 * When SelectFields encounters a type implementing this interface, it
 * transparently traverses into the wrapper's fields to build the correct
 * SQL select/with arrays for the underlying model type.
 *
 * Any custom wrapper class used with GraphQL::wrapType() or configured
 * via graphql.pagination_type / graphql.simple_pagination_type /
 * graphql.cursor_pagination_type must implement this interface.
 */
interface WrapType
{
}
