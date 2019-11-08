<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsAndContextTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Post',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'body' => [
                'type' => Type::string(),
            ],
            'comments' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Comment')))),
                'args' => [
                    'flag' => [
                        Type::boolean(),
                    ],
                ],
                'query' => function (array $args, HasMany $query, GraphQLContext $ctx): HasMany {
                    if (isset($ctx->data['flag'])) {
                        $query->where('comments.flag', '=', $ctx->data['flag']);
                    } elseif (isset($args['flag'])) {
                        $query->where('comments.flag', '=', $args['flag']);
                    }

                    return $query;
                },
            ],
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }
}
