<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateDiffNodeTests;

use GraphQL\Type\Definition\Type;
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
            'id' => [
                'type' => Type::ID(),
            ],
        ];
    }
}
