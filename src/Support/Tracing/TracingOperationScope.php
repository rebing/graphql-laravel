<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

/**
 * Carries tracing state through the GraphQL context value.
 *
 * Created by TracingExecutionMiddleware when field tracing is enabled,
 * wrapping the user's original context. TracingResolverMiddleware
 * unwraps this before any other resolver middleware runs, so
 * downstream code always sees the original context.
 *
 * This design avoids storing per-operation mutable state on the
 * singleton TracingManager, making tracing safe for concurrent
 * execution (e.g. fibers, nested GraphQL calls).
 *
 * @internal
 */
readonly class TracingOperationScope
{
    public function __construct(
        public mixed $originalContext,
        public TracingDriver $driver,
    ) {
    }
}
