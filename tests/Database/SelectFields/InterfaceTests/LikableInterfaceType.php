<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Comment;

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
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'alias' => 'title',
            ],
        ];
    }

    public function resolveType($root)
    {
        if ($root instanceof Post) {
            return GraphQL::type('Post');
        }
        if ($root instanceof Comment) {
            return GraphQL::type('Comment');
        }
    }
}
