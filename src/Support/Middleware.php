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
     */
    public function resolve(array $arguments, Closure $next): mixed
    {
        return $this->handle(...$arguments, ...[ // @phpstan-ignore argument.type (Pipeline guarantees correct argument types at runtime)
            function (...$arguments) use ($next) {
                return $next($arguments);
            },
        ]);
    }
}
