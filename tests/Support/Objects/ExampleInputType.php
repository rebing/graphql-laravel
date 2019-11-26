<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class ExampleInputType extends InputType
{
    protected $attributes = [
        'name' => 'ExampleInput',
        'description' => 'An example input',
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
