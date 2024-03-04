<?php declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Middlewares;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class GlobalMiddleware extends Middleware
{
    /**
     * @phpstan-param mixed $root
     * @phpstan-param mixed $context
     * @phpstan-return mixed
     */
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next): mixed
    {
        return [['test' => 'Intercepted by GlobalMiddleware']];
    }
}
