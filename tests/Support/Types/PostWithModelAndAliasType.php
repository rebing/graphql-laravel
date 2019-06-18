<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PostWithModelAndAliasType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PostWithModelAndAlias',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'description' => [
                'type' => Type::nonNull(Type::string()),
                'alias' => 'title',
            ],
        ];
    }
}
