<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Comment;

class CommentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Comment',
        'model' => Comment::class,
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
            'body' => [
                'type' => Type::string(),
            ],
            'file' => [
                'type' => GraphQL::type('File'),
            ],
            'commentable' => [
                'type' => GraphQL::type('CommentableUnion'),
            ],
        ];
    }
}
