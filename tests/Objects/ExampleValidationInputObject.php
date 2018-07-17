<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Traits\ShouldValidate;
use Rebing\GraphQL\Support\Type as BaseType;

class ExampleValidationInputObject extends BaseType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ExampleValidationInputObject'
    ];

    public function type()
    {
        return Type::listOf(Type::string());
    }

    public function fields()
    {
        return [
            'val' => [
                'name' => 'val',
                'type' => Type::int(),
                'rules' => ['required']
            ],
            'nest' => [
                'name' => 'nest',
                'type' => GraphQL::type('ExampleNestedValidationInputObject'),
                'rules' => ['required']
            ],
            'list' => [
                'name' => 'list',
                'type' => Type::listOf(GraphQL::type('ExampleNestedValidationInputObject')),
                'rules' => ['required']
            ],
        ];
    }

    public function resolve($root, $args)
    {
        return ['test'];
    }
}
