<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class InterfaceImpl1Type extends GraphQLType
{
    protected $attributes = [
        'name' => 'InterfaceImpl1',
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type('ExampleInterface'),
        ];
    }
}
