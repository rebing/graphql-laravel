<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

abstract class Middleware
{
    /**
     * @param array<string,mixed> $args
     */
    public function handle(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $next): mixed
    {
        return $next($root, $args, $context, $info);
    }

    /**
     * @param array<int, mixed> $arguments
     *
     * @see Field::getResolver()  Middleware is resolved in the field resolver pipeline
     * @param array{0:mixed,1:array<string,mixed>,2:mixed,3:ResolveInfo} $arguments
     */
    public function resolve(array $arguments, Closure $next): mixed
    {
        return $this->handle(...$arguments, ...[
            function (...$arguments) use ($next) {
                return $next($arguments);
            },
        ]);
    }
}
