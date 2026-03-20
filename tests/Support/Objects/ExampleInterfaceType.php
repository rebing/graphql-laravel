<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;

class ExampleInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'ExampleInterface',
        'description' => 'An example interface',
    ];

    public function resolveType(mixed $root): ScalarType
    {
        return Type::string();
    }

    public function fields(): array
    {
        return [
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field',
            ],
        ];
    }
}
