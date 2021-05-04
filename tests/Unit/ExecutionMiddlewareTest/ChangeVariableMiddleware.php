<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use Rebing\GraphQL\Support\ExecutionMiddleware\ExecutionMiddleware;

class ChangeVariableMiddleware extends ExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle($query, $args, array $opts, Closure $next)
    {
        $args['index'] = (int) $args['index'];

        return $next($query, $args, $opts);
    }
}
