<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Type\User;

use Models\User;
use GraphQL\GraphQL;
use GraphQL\Privacy\MePrivacy;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'description' => 'A user',
        'model' => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the user',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'Email of the user',
                'privacy' => MePrivacy::class,
            ],
            'avatar' => [
                'type' => Type::string(),
                'description' => 'Avatar (picture) of the user',
                'alias' => 'display_picture', // Column name in database
            ],
            'cover' => [
                'type' => Type::string(),
                'description' => 'Cover (picture) of the user',
            ],
            'confirmed' => [
                'type' => Type::boolean(),
                'description' => 'Confirmed status of the user',
            ],
            'pin' => [
                'type' => Type::string(),
                'description' => 'Pin (ID code) of the user',
            ],

            /* RELATIONS */
            'profile' => [
                'type' => GraphQL::type('user_profile'),
                'description' => 'User profile',
            ],
        ];
    }
}
