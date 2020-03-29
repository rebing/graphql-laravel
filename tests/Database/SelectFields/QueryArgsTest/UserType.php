<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsTest;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\User;

class UserType extends GraphQLType
{
    /**
     * @var array<string, mixed>
     */
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
                    'publishedAfter' => [
                        Type::string(),
                    ],
                ],
                'query' => function (array $args, HasMany $query, $ctx): HasMany {
                    if (isset($args['flag']) && $args['flag']) {
                        $query->where('posts.flag', '=', $args['flag']);
                    }
                    if (isset($args['publishedAfter'])) {
                        $query->where('posts.published_at', '>', $args['publishedAfter']);
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
