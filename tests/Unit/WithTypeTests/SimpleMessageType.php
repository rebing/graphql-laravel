<?php

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SimpleMessageType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'SimpleMessageType',
        'description' => 'A type of a simple message',
    ];

    public function fields() : array
    {
        return [
            'message' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'Simple Message',
            ],
            'type' => [
                'type'         => Type::string(),
                'defaultValue' => 'success',
            ],
        ];
    }
}
