<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PostType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PostType',
        'description' => 'Post type',
    ];

    public function fields() : array
    {
        return [
            'post_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Simple Message',
            ],
            'title' => [
                'type' => Type::string(),
                'defaultValue' => 'success',
            ],
        ];
    }
}
