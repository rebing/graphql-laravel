<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TerminableMiddlewareTest;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

/**
 * Test middleware that records calls to both `handle()` and `terminate()`
 * so we can verify Laravel's terminating lifecycle invokes the latter
 * after the HTTP response is sent.
 */
class TerminableMiddleware extends Middleware
{
    public static int $handleCalls = 0;

    public static int $terminateCalls = 0;

    /** @var list<array{root: mixed, args: array<string, mixed>, result: mixed}> */
    public static array $terminateArguments = [];

    public function handle(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $next): mixed
    {
        self::$handleCalls++;

        return $next($root, $args, $context, $info);
    }

    /**
     * @param array<string,mixed> $args
     */
    public function terminate(mixed $root, array $args, mixed $context, ResolveInfo $info, mixed $result): void
    {
        self::$terminateCalls++;
        self::$terminateArguments[] = [
            'root' => $root,
            'args' => $args,
            'result' => $result,
        ];
    }
}
