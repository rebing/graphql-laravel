<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class ExampleValidationField extends Field
{
    protected $attributes = [
        'name' => 'example_validation',
    ];

    public function type(): Type
    {
        return Type::listOf(Type::string());
    }

    public function args(): array
    {
        return [
            'index' => [
                'name' => 'index',
                'type' => Type::int(),
                'rules' => ['required'],
            ],
        ];
    }

    public function resolve($root, $args): array
    {
        return ['test'];
    }
}
