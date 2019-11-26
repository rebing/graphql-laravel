<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InterfaceType;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;

class LikableInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'LikableInterface',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('Post'),
            GraphQL::type('Comment'),
        ];
    }

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'likes' => [
                'type' => Type::listOf(GraphQL::type('Like')),
                'query' => function (array $args, MorphMany $query) {
                    return $query->whereRaw('1=1');
                },
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
