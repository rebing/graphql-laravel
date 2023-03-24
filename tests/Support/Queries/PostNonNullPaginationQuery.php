<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostNonNullPaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'postNonNullPaginationQuery',
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::paginate(Type::nonNull(GraphQL::type('PostWithModel'))->toString()));
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Post::query()
            ->select($getSelectFields()->getSelect())
            ->paginate();
    }
}
