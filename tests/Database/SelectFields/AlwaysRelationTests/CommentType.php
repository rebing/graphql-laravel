<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysRelationTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Comment;

class CommentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Comment',
        'model' => Comment::class,
    ];

    public function fields(): array
    {
        return [
            'body' => [
                'type' => Type::string(),
            ],
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
            'likes' => [
                'type' => Type::listOf(GraphQL::Type('Like')),
            ],
            'post' => [
                'type' => Type::nonNull(GraphQL::type('Post')),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
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
