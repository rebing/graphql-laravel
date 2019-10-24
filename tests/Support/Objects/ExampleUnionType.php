<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

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

    public function resolveType($root)
    {
        return GraphQL::type('Example');
    }
}
