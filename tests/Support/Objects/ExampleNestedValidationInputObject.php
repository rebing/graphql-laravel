<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use Rebing\GraphQL\Support\Type as BaseType;

class ExampleNestedValidationInputObject extends BaseType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ExampleNestedValidationInputObject',
    ];

    public function type(): ListOfType
    {
        return Type::listOf(Type::string());
    }

    public function fields(): array
    {
        return [
            'email' => [
                'name'  => 'email',
                'type'  => Type::string(),
                'rules' => ['email'],
            ],
        ];
    }

    public function resolve($root, $args): array
    {
        return ['test'];
    }
}
