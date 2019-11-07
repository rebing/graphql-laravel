<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\EngineErrorInResolverTests;

use TypeError;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class QueryWithEngineErrorInCodeQuery extends Mutation
{
    protected $attributes = [
        'name' => 'queryWithEngineErrorInCode',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function resolve($root, $args): string
    {
        throw new TypeError('Simulating a TypeError');
    }
}
