<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

/**
 * Resolver middleware that instruments individual field resolutions.
 *
 * Wraps each field's resolve() call with TracingDriver::startFieldResolve()
 * and TracingDriver::endFieldResolve().
 *
 * Tracing state is carried through the GraphQL context value as a
 * {@see TracingOperationScope}, making this middleware safe for concurrent
 * execution (fibers, nested GraphQL calls). The scope is unwrapped before
 * calling the next middleware, so downstream code always sees the original
 * context.
 *
 * This middleware is automatically prepended as a global resolver middleware
 * when any schema has field_tracing enabled. It must run before any other
 * resolver middleware so it can unwrap the context transparently.
 */
class TracingResolverMiddleware extends Middleware
{
    /**
     * @param array<string,mixed> $args
     */
    public function handle(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $next): mixed
    {
        if (!$context instanceof TracingOperationScope) {
            return $next($root, $args, $context, $info);
        }

        $originalContext = $context->originalContext;
        $driver = $context->driver;

        $traceContext = $driver->startFieldResolve($info);

        try {
            return $next($root, $args, $originalContext, $info);
        } finally {
            $driver->endFieldResolve($traceContext, $info);
        }
    }
}
