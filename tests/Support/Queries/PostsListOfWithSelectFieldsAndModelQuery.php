<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostsListOfWithSelectFieldsAndModelQuery extends Query
{
    protected $attributes = [
        'name' => 'postsListOfWithSelectFieldsAndModel',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('PostWithModel'));
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Post
            ::select($getSelectFields()->getSelect())
            ->get();
    }
}
