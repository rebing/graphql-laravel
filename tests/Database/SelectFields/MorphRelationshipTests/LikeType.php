<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\MorphRelationshipTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Support\Type as GraphQLType;

class LikeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Like',
        'model' => Like::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'likable' => [
                'type' => Type::nonNull(GraphQL::type('LikableInterface')),
            ],
            'user' => [
                'type' => Type::nonNull(GraphQL::type('User')),
            ],
        ];
    }
}
