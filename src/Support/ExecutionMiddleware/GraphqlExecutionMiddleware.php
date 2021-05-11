<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\OperationParams;

/**
 * This middleware is always added via \Rebing\GraphQL\GraphQL::appendGraphqlExecutionMiddleware
 */
class GraphqlExecutionMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $query = $params->getParsedQuery();

        $defaultFieldResolver = config('graphql.defaultFieldResolver');

        return GraphQLBase::executeQuery($schema, $query, $rootValue, $contextValue, $params->variables, $params->operation, $defaultFieldResolver);
    }
}
