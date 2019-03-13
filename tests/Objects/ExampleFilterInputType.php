<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleFilterInputType extends GraphQLType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ExampleFilterInput',
        'description' => 'A nested filter input with self reference'
    ];

    public function fields()
    {
        return [
            'AND' => [
                'type' => Type::listOf(Type::nonNull(GraphQL::type('ExampleFilterInput'))),
                'description' => 'List of self references'
            ],
            'test' => [
                'type' => Type::String(),
                'description' => 'Test field filter'
            ],
            // This field can trigger an infinite recursion
            // in case recursion in Field.getRules() is not handled correctly
            'parent' => [
                'type' => GraphQL::type('ExampleFilterInput'),
                'description' => 'Self reference'
            ]
        ];
    }
}
