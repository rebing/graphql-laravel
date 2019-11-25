<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class CustomExampleType extends GraphQLType
{
    protected $attributes = [
        'name' => 'CustomExample',
        'description' => 'An example',
    ];

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
