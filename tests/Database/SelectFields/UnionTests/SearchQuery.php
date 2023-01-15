<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;

class SearchQuery extends Query
{
    protected $attributes = [
        'name' => 'searchQuery',
    ];

    public function type(): Type
    {
        return GraphQL::type('SearchUnion');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    public function resolve($root, $args, $ctx, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        if (Str::startsWith($args['id'], 'comment:')) {
            return Comment::select($selectFields->getSelect())
                ->with($selectFields->getRelations())
                ->where('id', (int) Str::after($args['id'], 'comment:'))
                ->first();
        }

        return Post::select($selectFields->getSelect())
            ->with($selectFields->getRelations())
            ->where('id', (int) $args['id'])
            ->first();
    }
}
