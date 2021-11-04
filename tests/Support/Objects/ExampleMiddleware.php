<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use Closure;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class ExampleMiddleware extends Middleware
{
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
    {
        if (4 === $args['index']) {
            $args['index'] = 0;
        }

        if (5 === $args['index']) {
            throw new Exception('Index 5 is not allowed');
        }

        $result = $next($root, $args, $context, $info);

        if ('Example 2' === $result[0]['test']) {
            $result[0]['test'] = 'ExampleMiddleware changed me!';
        }

        if ('Example 3' === $result[0]['test']) {
            throw new Exception('Example 3 is not allowed');
        }

        return $result;
    }

    public function terminate($root, $args, $context, ResolveInfo $info, $result): void
    {
        if (6 === $args['index']) {
            throw new Exception('Terminate happens after the response is sent');
        }
    }
}
