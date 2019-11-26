<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleType2 extends GraphQLType
{
    protected $attributes = [
        'name' => 'Example2',
        'description' => 'An example',
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
