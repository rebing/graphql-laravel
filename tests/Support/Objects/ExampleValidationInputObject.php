<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class ExampleValidationInputObject extends InputType
{
    protected $attributes = [
        'name' => 'ExampleValidationInputObject',
    ];

    public function type(): ListOfType
    {
        return Type::listOf(Type::string());
    }

    public function fields(): array
    {
        return [
            'val' => [
                'name' => 'val',
                'type' => Type::int(),
                'rules' => ['required'],
            ],
            'nest' => [
                'name' => 'nest',
                'type' => GraphQL::type('ExampleNestedValidationInputObject'),
                'rules' => ['required'],
            ],
            'list' => [
                'name' => 'list',
                'type' => Type::listOf(GraphQL::type('ExampleNestedValidationInputObject')),
                'rules' => ['required'],
            ],
        ];
    }

    public function resolve($root, $args): array
    {
        return ['test'];
    }
}
