<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\MorphRelationshipTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InterfaceType;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;

class LikableInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'LikableInterface',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ];
    }

    public function resolveType($root)
    {
        if ($root instanceof Post) {
            return GraphQL::type('Post');
        } elseif ($root instanceof Comment) {
            return GraphQL::type('Comment');
        }
    }
}
