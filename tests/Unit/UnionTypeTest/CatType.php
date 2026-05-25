<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UnionTypeTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class CatType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Cat',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'meow_volume' => [
                'type' => Type::nonNull(Type::int()),
            ],
        ];
    }
}
