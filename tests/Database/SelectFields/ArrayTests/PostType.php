<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ArrayTests;

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
                'type' => Type::nonNull(Type::ID()),
            ],
            'properties' => [
                'type' => Type::listOf(GraphQL::type('Property')),
                'is_relation' => false,
            ],
        ];
    }
}
