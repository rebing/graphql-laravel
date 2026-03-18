<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;
use RuntimeException;

/**
 * Test middleware that throws an exception instead of calling $next.
 */
class ThrowingExecutionMiddleware extends AbstractExecutionMiddleware
{
    public function handle(
        string $schemaName,
        Schema $schema,
        OperationParams $params,
        $rootValue,
        $contextValue,
        Closure $next,
    ): ExecutionResult {
        throw new RuntimeException('Test exception');
    }
}
