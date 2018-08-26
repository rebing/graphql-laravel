<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as BaseType;

class ExampleNestedValidationInputObject extends BaseType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ExampleNestedValidationInputObject'
    ];

    public function type()
    {
        return Type::listOf(Type::string());
    }

    public function fields()
    {
        return [
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'rules' => ['email']
            ],
        ];
    }

    public function resolve($root, $args)
    {
        return ['test'];
    }
}
