<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PrimaryKeyCursorPaginationQuery extends Query
{
    /** @var array<string, string> */
    protected $attributes = [
        'name' => 'primaryKeyCursorPaginationQuery',
    ];

    public function type(): Type
    {
        return GraphQL::cursorPaginate('Post');
    }

    /**
     * @param mixed $root
     * @param mixed $args
     * @param mixed $ctx
     *
     * @return CursorPaginator<Post>
     */
    public function resolve($root, $args, $ctx, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return Post::with($selectFields->getRelations())
            ->select($selectFields->getSelect())
            ->cursorPaginate(1);
    }
}
