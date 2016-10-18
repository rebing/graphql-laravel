<?php

namespace Rebing\GraphQL\Type\User;

use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
use Models\UserProfile; // not included in this project
use GraphQL\GraphQL;

class UserProfileType extends GraphQLType {

    protected $attributes = [
        'name'          => 'User profile',
        'description'   => 'A user\'s profile',
        'model'         => UserProfile::class,
    ];

    public function fields()
    {
        return [
            'user_id' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'User id',
            ],
            'first_name' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'First name of the user',
            ],
            'last_name' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'Last name of the user',
            ],
            'birth_date' => [
                'type'          => Type::string(),
                'description'   => 'Birth date as date',
            ],
            'iban' => [
                'type'          => Type::string(),
                'description'   => 'IBAN of the user',
            ],
            'phone' => [
                'type'          => Type::string(),
                'description'   => 'Phone number',
            ],
            'height' => [
                'type'          => Type::float(),
                'description'   => 'Height (in cm)',
            ],

            /* RELATIONS */
            'location' => [
                'type'          => GraphQL::type('location'),
                'description'   => 'Location of the user',
            ],
        ];
    }

}