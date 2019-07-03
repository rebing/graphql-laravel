<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysMorphTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PostType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Post',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'user' => [
                'type' => GraphQL::type('User'),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'likes' => [
                'type' => Type::listOf(GraphQL::Type('Like')),
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
