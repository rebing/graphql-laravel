<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use GraphQL\Type\Definition\Type as GraphqlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Product;

class CommentableUnionType extends UnionType
{
    protected $attributes = [
        'name' => 'CommentableUnion',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('Post'),
            GraphQL::type('Product'),
        ];
    }

    public function relationName(): array
    {
        return [
            Post::class    => 'post',
            Product::class => 'product',
        ];
    }

    /**
     * @param object $value
     */
    public function resolveType($value): ?GraphqlType
    {  
        if ($value instanceof Post) {
            return GraphQL::type('Post');
        }
        if ($value instanceof Product) {
            return GraphQL::type('Product');
        }
        return null;
    }
}


