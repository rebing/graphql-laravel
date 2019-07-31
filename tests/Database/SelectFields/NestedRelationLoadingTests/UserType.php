<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\NestedRelationLoadingTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
                        Type::boolean(),
                    ],
                    'filter' => [
                        'type' => GraphQL::type('Filter')
                    ]
                ],
                'query' => function (array $args, HasMany $query): HasMany {
                    if (isset($args['flag'])) {
                        $query->where('posts.flag', '=', $args['flag']);
                    }
                    if (isset($args['filter'])) {
                        if (isset($args['filter']['title'])) {
                            $query->where('posts.title', 'like', "%{$args['filter']['title']}%");
                        }
                        if (isset($args['filter']['body'])) {
                            $query->where('posts.title', 'like', "%{$args['filter']['title']}%");
                        }
                        if (isset($args['filter']['keywords'])) {
                            $query->whereHas('tags', function($query) use ($args) {
                                $query->whereIn('name', $args['filter']['keywords']);
                            });
                        }
                    }

                    return $query;
                },
            ],
            'flaggedPosts' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Post')))),
                'alias' => 'posts',
                'query' => function (array $args, HasMany $query): HasMany {
                    $query->where('posts.flag', '=', 1);

                    return $query;
                },
            ],
        ];
    }
}
