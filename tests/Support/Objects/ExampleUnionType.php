<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;

class ExampleUnionType extends UnionType
{
    protected $attributes = [
        'name' => 'ExampleUnion',
        'description' => 'An example union',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('Example'),
        ];
    }

    public function resolveType(mixed $root): Type
    {
        return GraphQL::type('Example');
    }
}
