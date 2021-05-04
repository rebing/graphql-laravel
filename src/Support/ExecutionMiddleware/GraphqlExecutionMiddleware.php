<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\GraphQL as GraphQLBase;
use Illuminate\Container\Container;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\Support\OperationParams;

/**
 * This middleware is always added via \Rebing\GraphQL\GraphQL::appendGraphqlExecutionMiddleware
 */
class GraphqlExecutionMiddleware extends AbstractExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        /** @var GraphQL $graphql */
        $graphql = Container::getInstance()->make('graphql');

        $schema = $graphql->schema($schemaName);

        $query = $params->getParsedQuery();

        $defaultFieldResolver = config('graphql.defaultFieldResolver');

        return GraphQLBase::executeQuery($schema, $query, $rootValue, $contextValue, $params->variables, $params->operation, $defaultFieldResolver);
    }
}
