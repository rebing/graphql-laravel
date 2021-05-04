<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsAndContextTests;

use Closure;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class CustomGraphqlContextMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        return $next($schemaName, $params, $rootValue, new GraphQLContext());
    }
}
