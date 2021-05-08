<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\GraphQL as GraphQLBase;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\Support\OperationParams;

/**
 * This middleware is always added via \Rebing\GraphQL\GraphQL::appendGraphqlExecutionMiddleware
 */
class GraphqlExecutionMiddleware extends AbstractExecutionMiddleware
{
    /** @var GraphQL */
    private $graphql;

    public function __construct(GraphQL $graphql)
    {
        $this->graphql = $graphql;
    }
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        $schema = $this->graphql->schema($schemaName);

        $query = $params->getParsedQuery();

        $defaultFieldResolver = config('graphql.defaultFieldResolver');

        return GraphQLBase::executeQuery($schema, $query, $rootValue, $contextValue, $params->variables, $params->operation, $defaultFieldResolver);
    }
}
