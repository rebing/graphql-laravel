<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\AliasedRelationArgTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\User;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'model' => User::class,
    ];

    public function fields(): array
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
                'args' => [
                    'flag' => [
                        'type' => Type::boolean(),
                    ],
                ],
                'query' => function (array $args, HasMany $query): HasMany {
                    if (isset($args['flag'])) {
                        $query->where(DB::raw('posts.flag'), '=', $args['flag']);
                    }

                    return $query;
                },
            ],
        ];
    }
}
