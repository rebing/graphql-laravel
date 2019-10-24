<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ExamplesConfigAliasQuery extends Query
{
    protected $attributes = [
        'name' => 'examplesAlias',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('ExampleConfigAlias'));
    }

    public function args(): array
    {
        return [
            'index' => ['name' => 'index', 'type' => Type::int()],
        ];
    }

    public function resolve($root, $args)
    {
        $data = include __DIR__ . '/data.php';

        if (isset($args['index'])) {
            return [
                $data[$args['index']],
            ];
        }

        return $data;
    }
}
