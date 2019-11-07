<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ReturnScalarQuery extends Query
{
    protected $attributes = [
        'name' => 'returnScalar',
    ];

    public function type(): Type
    {
        return GraphQL::type('MyCustomScalarString');
    }

    public function resolve(): string
    {
        return 'just a string';
    }
}
