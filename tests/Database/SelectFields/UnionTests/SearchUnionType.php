<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;

class SearchUnionType extends UnionType
{
    protected $attributes = [
        'name' => 'SearchUnion',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('Post'),
            GraphQL::type('Comment'),
        ];
    }

    /**
     * @param object $value
     * @return Type|null
     */
    public function resolveType($value): ?Type
    {
        if ($value instanceof Post) {
            return GraphQL::type('Post');
        } elseif ($value instanceof Comment) {
            return GraphQL::type('Comment');
        } else {
            return null;
        }
    }
}
