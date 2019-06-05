<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\NestedRelationLoadingTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'model' => User::class,
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'posts' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Post')))),
            ],
        ];
    }
}
