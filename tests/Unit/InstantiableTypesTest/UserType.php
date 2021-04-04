<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\InstantiableTypesTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'UserType',
        'description' => 'User type',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::id(),
            ],
            'dateOfBirth' => new FormattableDate(),
            'createdAt' => new FormattableDate([
                'alias' => 'created_at',
            ]),
        ];
    }
}
