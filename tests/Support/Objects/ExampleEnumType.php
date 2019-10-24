<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\EnumType;

class ExampleEnumType extends EnumType
{
    protected $attributes = [
        'name' => 'ExampleEnum',
        'description' => 'An example enum',
        'values' => [
            'TEST' => [
                'value' => 1,
                'description' => 'test',
            ],
        ],
    ];

    public function fields(): array
    {
        return [
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field',
            ],
            'test_validation' => ExampleValidationField::class,
        ];
    }
}
