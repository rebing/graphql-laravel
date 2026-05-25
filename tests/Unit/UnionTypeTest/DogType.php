<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UnionTypeTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class DogType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Dog',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'good_boy' => [
                'type' => Type::nonNull(Type::boolean()),
            ],
        ];
    }
}
