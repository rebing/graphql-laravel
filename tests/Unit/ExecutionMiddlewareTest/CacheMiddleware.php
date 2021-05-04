<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class CacheMiddleware extends AbstractExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        return new ExecutionResult([
                'examples' => [['test' => 'Cached response']],
        ]);
    }
}
