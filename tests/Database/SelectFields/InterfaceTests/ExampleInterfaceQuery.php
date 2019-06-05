<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Closure;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;

class ExampleInterfaceQuery extends Query
{
    protected $attributes = [
        'name' => 'exampleInterfaceQuery',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('ExampleInterface'));
    }

    public function resolve($root, $args, $contxt, ResolveInfo $info, Closure $getSelectFields)
    {
        return Post
            ::select($getSelectFields()->getSelect())
            ->get();
    }
}
