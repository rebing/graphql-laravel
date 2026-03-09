<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Comment;

class CommentsQuery extends Query
{
    protected $attributes = [
        'name' => 'comments',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Comment'));
    }

    public function resolve($root, $args, $context, ResolveInfo $info, Closure $getSelectFields): array|\Illuminate\Database\Eloquent\Collection
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return Comment::query()
            ->select($selectFields->getSelect())
            ->with($selectFields->getRelations())
            ->get();
    }
}
