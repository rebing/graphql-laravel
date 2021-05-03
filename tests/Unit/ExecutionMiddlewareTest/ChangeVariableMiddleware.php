<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use Rebing\GraphQL\Support\ExecutionMiddleware;

class ChangeVariableMiddleware extends ExecutionMiddleware
{
    /**
     * @param string|mixed $query
     * @param array<string,mixed> $args
     * @return Closure|array<mixed>
     */
    public function handle($query, $args, Closure $next)
    {
        $args['index'] = (int) $args['index'];

        return $next($query, $args);
    }
}
