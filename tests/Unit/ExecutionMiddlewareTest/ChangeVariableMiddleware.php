<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class ChangeVariableMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $params->variables['index'] = (int) $params->variables['index'];

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }
}
