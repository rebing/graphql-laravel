<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PrimaryKeyQuery extends Query
{
    protected $attributes = [
        'name' => 'primaryKeyQuery',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Post'));
    }

    public function resolve($root, $args, $ctx, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return Post
            ::with($selectFields->getRelations())
            ->select($selectFields->getSelect())
            ->get();
    }
}
