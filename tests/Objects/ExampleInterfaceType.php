<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;

class ExampleInterfaceType extends InterfaceType
{

    protected $attributes = [
        'name' => 'ExampleInterface',
        'description' => 'An example interface'
    ];

    public function resolveType($root)
    {
        return Type::string();
    }

    public function fields()
    {
        return [
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field'
            ]
        ];
    }
}
