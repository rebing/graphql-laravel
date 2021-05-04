<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use Rebing\GraphQL\Support\ExecutionMiddleware\ExecutionMiddleware;

class CacheMiddleware extends ExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle($query, $args, array $opts, Closure $next)
    {
        return new ExecutionResult([
                'examples' => [['test' => 'Cached response']],
        ]);
    }
}
