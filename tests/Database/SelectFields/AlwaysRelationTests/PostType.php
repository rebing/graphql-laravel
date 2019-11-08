<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysRelationTests;

use GraphQL\Type\Definition\Type;
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
                'type' => Type::nonNull(Type::string()),
            ],
            'comments' => [
                'type' => Type::listOf(GraphQL::type('Comment')),
            ],
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'likes' => [
                'type' => Type::listOf(GraphQL::Type('Like')),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'user' => [
                'type' => GraphQL::type('User'),
            ],
        ];
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type('LikableInterface'),
        ];
    }
}
