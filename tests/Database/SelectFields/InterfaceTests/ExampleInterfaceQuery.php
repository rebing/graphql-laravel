<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;

class ExampleInterfaceQuery extends Query
{
    protected $attributes = [
        'name' => 'exampleInterfaceQuery',
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type('ExampleInterface'));
    }

    public function resolve($root, $args, SelectFields $selectFields)
    {
        return Post
            ::select($selectFields->getSelect())
            ->get();
    }
}
