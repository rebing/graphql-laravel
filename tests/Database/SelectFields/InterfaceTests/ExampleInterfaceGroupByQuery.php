<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\Post;

class ExampleInterfaceGroupByQuery extends Query
{
    protected $attributes = [
        'name' => 'exampleInterfaceGroupByQuery',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('ExampleInterface'));
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $getSelectFields): Collection
    {
        $fields = $getSelectFields();

        return Post::select($fields->getSelect())
            ->with($fields->getRelations())
            ->groupBy('title')
            ->get();
    }
}
