<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use Rebing\GraphQL\Support\ExecutionMiddleware\ExecutionMiddleware;

class CacheMiddleware extends ExecutionMiddleware
{
    /**
     * @param string|mixed $query
     * @param array<string,mixed> $args
     * @return Closure|array<mixed>
     */
    public function handle($query, $args, Closure $next)
    {
        return new ExecutionResult([
                'examples' => [['test' => 'Cached response']],
        ]);
    }
}
