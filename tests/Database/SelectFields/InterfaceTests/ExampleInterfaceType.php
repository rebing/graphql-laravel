<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InterfaceType;

class ExampleInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'ExampleInterface',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'exampleRelation' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ExampleRelation')))),
                'query' => function (array $args, HasMany $query): HasMany {
                    return $query->where('id', '>=', 1);
                },
                'alias' => 'comments',
            ],
        ];
    }

    public function resolveType()
    {
        return GraphQL::type('InterfaceImpl1');
    }
}
