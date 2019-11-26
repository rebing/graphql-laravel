<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Post;

class AlwaysQuery extends Query
{
    protected $attributes = [
        'name' => 'alwaysQuery',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Post'));
    }

    public function resolve($root, $args, $contxt, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return Post
            ::select($selectFields->getSelect())
            ->with($selectFields->getRelations())
            ->get();
    }
}
