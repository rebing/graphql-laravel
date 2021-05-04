<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Executor\ExecutionResult;

abstract class ExecutionMiddleware
{
    /**
     * @param string|mixed $query
     * @param array<mixed>|mixed $args
     * @param array<string,mixed> $opts
     * @return Closure|array<mixed>|ExecutionResult
     */
    public function handle($query, $args, array $opts, Closure $next)
    {
        return $next($query, $args, $opts);
    }

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
