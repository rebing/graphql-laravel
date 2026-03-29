<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\LazyTypeTests;

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
                'type' => Type::nonNull(Type::id()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            // Circular back-reference: Post -> User -> posts -> Post ...
            // Uses a lazy thunk to break the cycle.
            'user' => [
                'type' => fn () => Type::nonNull(GraphQL::type('User')),
            ],
        ];
    }
}
