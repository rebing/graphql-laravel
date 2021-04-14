<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsAndContextTests;

use Rebing\GraphQL\GraphQLController as BaseGraphQLController;

class GraphQLController extends BaseGraphQLController
{
    protected function queryContext(string $query, ?array $variables, string $schemaName)
    {
        return new GraphQLContext();
    }
}
