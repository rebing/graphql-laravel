<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Product;

class ProductType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Product',
        'model' => Product::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'name' => [
                'type' => Type::string(),
            ],
            'price' => [
                'type' => Type::float(),
            ],
            'file' => [
                'type' => GraphQL::type('File'),
            ],
        ];
    }
}
