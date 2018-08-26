<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleInputType extends GraphQLType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ExampleInput',
        'description' => 'An example input'
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
