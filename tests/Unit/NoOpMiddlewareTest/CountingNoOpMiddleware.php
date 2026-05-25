<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NoOpMiddlewareTest;

use Closure;
use Rebing\GraphQL\Support\Middleware;

/**
 * Intentionally empty middleware: does NOT override `handle()`. This exercises
 * the default no-op `Middleware::handle()` implementation in the base class,
 * which simply forwards `$next(...)`.
 *
 * Tracks how many times `handle()` and `resolve()` are invoked so the test
 * can verify the parent's pass-through actually ran.
 */
class CountingNoOpMiddleware extends Middleware
{
    public static int $resolveCalls = 0;

    public function resolve(array $arguments, Closure $next): mixed
    {
        self::$resolveCalls++;

        return parent::resolve($arguments, $next);
    }
}
