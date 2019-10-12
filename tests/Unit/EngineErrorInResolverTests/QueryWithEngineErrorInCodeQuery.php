<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\EngineErrorInResolverTests;

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
        // This code deliberately creates a PHP error!
        return $this->getResult('result');
    }

    private function getResult(int $string): string
    {
    }
}
