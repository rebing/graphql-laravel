<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\OperationParams;

abstract class AbstractExecutionMiddleware
{
    /**
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @return Closure|array<mixed>|ExecutionResult
     */
    abstract public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next);

    /**
     * @param array<string,mixed> $arguments
     * @return Closure|array<mixed>|ExecutionResult
     */
    public function resolve(array $arguments, Closure $next)
    {
        return $this->handle(...$arguments, ...[
            function (...$arguments) use ($next) {
                return $next($arguments);
            },
        ]);
    }
}
