<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ExamplesMiddlewareQuery extends Query
{
    protected $attributes = [
        'name' => 'examples',
    ];

    protected $middleware = [
        ExampleMiddleware::class,
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Example'));
    }

    public function args(): array
    {
        return [
            'index' => [
                'type' => Type::int(),
            ],
        ];
    }

    public function resolve($root, $args)
    {
        $data = include __DIR__.'/data.php';

        if (isset($args['index'])) {
            return [
                $data[$args['index']],
            ];
        }

        return $data;
    }
}
