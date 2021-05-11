<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsAndContextTests;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class CustomGraphqlContextMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        return $next($schemaName, $schema, $params, $rootValue, new GraphQLContext());
    }
}
