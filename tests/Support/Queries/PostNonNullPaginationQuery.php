<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return GraphQL::paginate('PostWithModel');
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): LengthAwarePaginator
    {
        return Post::query()
            ->select($getSelectFields()->getSelect())
            ->paginate();
    }
}
