<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NoOpMiddlewareTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class GreetingQuery extends Query
{
    protected $attributes = [
        'name' => 'greet',
    ];

    /** @var list<class-string> */
    protected $middleware = [
        CountingNoOpMiddleware::class,
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args): string
    {
        return "Hello, {$args['name']}!";
    }
}
