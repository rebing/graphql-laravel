<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\UnionType as BaseUnionType;

class ExampleUnionType extends BaseUnionType
{
    protected $attributes = [
        'name' => 'ExampleUnion',
        'description' => 'An example union'
    ];

    public function types()
    {
        return [
            GraphQL::type('Example')
        ];
    }

    public function resolveType($root)
    {
        return GraphQL::type('Example');
    }

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
