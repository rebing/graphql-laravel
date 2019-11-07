<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ComputedPropertiesTests;

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
            'body' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'isPublished' => [
                'type' => Type::nonNull(Type::boolean()),
                'selectable' => false,
                'always' => 'published_at',
            ],
            'publishedAt' => [
                'type' => Type::string(),
                'alias' => 'published_at',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'user' => [
                'type' => GraphQL::type('User'),
            ],
        ];
    }
}
