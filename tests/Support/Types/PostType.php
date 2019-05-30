<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PostType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Post',
        'model' => Post::class,
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }
}
