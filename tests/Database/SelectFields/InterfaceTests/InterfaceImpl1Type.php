<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class InterfaceImpl1Type extends GraphQLType
{
    protected $attributes = [
        'name' => 'InterfaceImpl1',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        $interface = GraphQL::type('ExampleInterface');

        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ] + $interface->getFields();
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type('ExampleInterface'),
        ];
    }
}
