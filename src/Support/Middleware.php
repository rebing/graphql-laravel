<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

abstract class Middleware
{
    public function handle($root, $args, $context, ResolveInfo $info, Closure $next)
    {
        return $next($root, $args, $context, $info);
    }

    public function resolve(array $arguments, Closure $next)
    {
        return $this->handle(...$arguments, ...[
            function (...$arguments) use ($next) {
                return $next($arguments);
            },
        ]);
    }
}
