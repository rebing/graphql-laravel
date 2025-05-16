<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostNonNullCursorPaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'postNonNullCursorPaginationQuery',
    ];

    public function type(): Type
    {
        return GraphQL::cursorPaginate('PostWithModel');
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): CursorPaginator
    {
        return Post::query()
            ->select($getSelectFields()->getSelect())
            ->cursorPaginate();
    }
}
