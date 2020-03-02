<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class ExampleMiddleware extends Middleware
{
    public function handle($root, $args, $context, ResolveInfo $info, Closure $next)
    {
        if ($args['index'] === 4) {
            $args['index'] = 0;
        }

        if ($args['index'] === 5) {
            throw new \Exception('Index 5 is not allowed');
        }

        $result = $next($root, $args, $context, $info);

        if ($result[0]['test'] === 'Example 2') {
            $result[0]['test'] = 'ExampleMiddleware changed me!';
        }

        if ($result[0]['test'] === 'Example 3') {
            throw new \Exception('Example 3 is not allowed');
        }

        return $result;
    }

    public function terminate($root, $args, $context, ResolveInfo $info, $result): void
    {
        if ($args['index'] === 6) {
            throw new \Exception('Terminate happens after the response is sent');
        }
    }
}
