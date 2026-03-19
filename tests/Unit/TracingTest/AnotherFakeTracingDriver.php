<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Tracing\TracingDriver;

/**
 * A second fake tracing driver used to test per-schema driver overrides.
 */
class AnotherFakeTracingDriver implements TracingDriver
{
    public function startOperation(string $schemaName, ?string $operationName, ?string $operationType, ?string $source): mixed
    {
        return null;
    }

    public function endOperation(mixed $context, ExecutionResult $result): ExecutionResult
    {
        return $result;
    }

    public function startFieldResolve(ResolveInfo $info): mixed
    {
        return null;
    }

    public function endFieldResolve(mixed $context, ResolveInfo $info): void
    {
    }
}
