<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TerminableMiddlewareTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class TerminableExampleQuery extends Query
{
    protected $attributes = [
        'name' => 'terminableExample',
    ];

    /** @var list<class-string> */
    protected $middleware = [
        TerminableMiddleware::class,
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'value' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args): string
    {
        return "resolved-{$args['value']}";
    }
}
