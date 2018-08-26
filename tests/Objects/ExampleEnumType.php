<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleEnumType extends GraphQLType
{
    protected $enumObject = true;

    protected $attributes = [
        'name' => 'ExampleEnum',
        'description' => 'An example enum',
        'values' => [
            'TEST' => [
                'value' => 1,
                'description' => 'test'
            ]
        ]
    ];

    public function fields()
    {
        return [
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field'
            ],
            'test_validation' => ExampleValidationField::class
        ];
    }
}
