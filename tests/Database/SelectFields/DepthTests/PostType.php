<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\DepthTests;

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
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
            'user' => [
                'type' => Type::nonNull(GraphQL::type('User')),
            ],
        ];
    }
}
