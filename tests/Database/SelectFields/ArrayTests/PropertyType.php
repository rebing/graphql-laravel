<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ArrayTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PropertyType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Property',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::string(),
            ],
            'title' => [
                'type' => Type::string(),
            ],
        ];
    }
}
