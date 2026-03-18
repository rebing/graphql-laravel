<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Contract for tracing drivers.
 *
 * A tracing driver collects timing data during GraphQL execution and
 * may either attach it to the response or export it out-of-band
 * (e.g. OpenTelemetry).
 */
interface TracingDriver
{
    /**
     * Called before the GraphQL execution pipeline runs.
     *
     * @param string $schemaName The name of the schema being executed
     * @param string|null $operationName The operation name (if provided by the client)
     * @param string|null $operationType The operation type (query/mutation/subscription), resolved after parsing
     * @param string|null $source The GraphQL document source (query string)
     * @return mixed An opaque context value passed back to endOperation()
     */
    public function startOperation(string $schemaName, ?string $operationName, ?string $operationType, ?string $source): mixed;

    /**
     * Called after the GraphQL execution pipeline completes.
     *
     * Implementations may modify the result (e.g. to add tracing extensions)
     * or finalize out-of-band export.
     *
     * @param mixed $context The opaque context returned by startOperation()
     */
    public function endOperation(mixed $context, ExecutionResult $result): ExecutionResult;

    /**
     * Called before a field resolver executes.
     *
     * @param ResolveInfo $info The resolve info for the current field
     * @return mixed An opaque context value passed back to endFieldResolve()
     */
    public function startFieldResolve(ResolveInfo $info): mixed;

    /**
     * Called after a field resolver completes.
     *
     * @param mixed $context The opaque context returned by startFieldResolve()
     * @param ResolveInfo $info The resolve info for the current field
     */
    public function endFieldResolve(mixed $context, ResolveInfo $info): void;
}
