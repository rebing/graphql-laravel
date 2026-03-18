<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Tracing\TracingDriver;

/**
 * Reusable test double for TracingDriver.
 *
 * Tracks all calls for assertion in tests. An optional Closure can be
 * provided to customise the context returned by startFieldResolve().
 */
class FakeTracingDriver implements TracingDriver
{
    /** @var array{schemaName: string, operationName: ?string, operationType: ?string, source: ?string}|null */
    public ?array $startArgs = null;

    public bool $endCalled = false;

    public ?ExecutionResult $endResult = null;

    public int $startFieldCount = 0;

    public int $endFieldCount = 0;

    /** @var list<string> */
    public array $fieldNames = [];

    /** @var list<mixed> */
    public array $receivedContexts = [];

    /** @var Closure(ResolveInfo): mixed */
    private Closure $fieldContextFactory;

    public function __construct(?Closure $fieldContextFactory = null)
    {
        $this->fieldContextFactory = $fieldContextFactory ?? static fn (): mixed => null;
    }

    public function startOperation(string $schemaName, ?string $operationName, ?string $operationType, ?string $source): mixed
    {
        $this->startArgs = [
            'schemaName' => $schemaName,
            'operationName' => $operationName,
            'operationType' => $operationType,
            'source' => $source,
        ];

        return null;
    }

    public function endOperation(mixed $context, ExecutionResult $result): ExecutionResult
    {
        $this->endCalled = true;
        $this->endResult = $result;

        return $result;
    }

    public function startFieldResolve(ResolveInfo $info): mixed
    {
        $this->startFieldCount++;
        $this->fieldNames[] = $info->fieldName;

        return ($this->fieldContextFactory)($info);
    }

    public function endFieldResolve(mixed $context, ResolveInfo $info): void
    {
        $this->endFieldCount++;
        $this->receivedContexts[] = $context;
    }
}
