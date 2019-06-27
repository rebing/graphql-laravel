<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ExampleInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'ExampleInterface',
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    public function resolveType()
    {
        return GraphQL::type('InterfaceImpl1');
    }
}
