<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Middlewares;

use Closure;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class GlobalInstanceMiddleware extends Middleware
{
    protected int $invalidValue;

    public function __construct(int $invalidValue)
    {
        $this->invalidValue = $invalidValue;
    }

    /**
     * @phpstan-param mixed $root
     * @phpstan-param mixed $context
     * @phpstan-return mixed
     */
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next): mixed
    {
        if (isset($this->invalidValue) && $this->invalidValue === $args['index']) {
            throw new Exception('Index is not allowed');
        }

        return $next($root, $args, $context, $info);
    }
}
