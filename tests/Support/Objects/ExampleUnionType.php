<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType as BaseUnionType;

class ExampleUnionType extends BaseUnionType
{
    protected $attributes = [
        'name'        => 'ExampleUnion',
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

    public function fields(): array
    {
        return [
            'test' => [
                'type'        => Type::string(),
                'description' => 'A test field',
            ],
            'test_validation' => ExampleValidationField::class,
        ];
    }
}
