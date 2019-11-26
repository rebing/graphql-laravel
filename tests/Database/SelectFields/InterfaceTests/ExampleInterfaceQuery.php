<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
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
        $fields = $getSelectFields();

        return Post
            ::select($fields->getSelect())
            ->with($fields->getRelations())
            ->get();
    }
}
