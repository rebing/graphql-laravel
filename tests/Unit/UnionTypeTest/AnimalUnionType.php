<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UnionTypeTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;
use stdClass;

class AnimalUnionType extends UnionType
{
    protected $attributes = [
        'name' => 'Animal',
        'description' => 'A cat or a dog',
    ];

    /**
     * @return list<Type>
     */
    public function types(): array
    {
        return [
            GraphQL::type('Cat'),
            GraphQL::type('Dog'),
        ];
    }

    public function resolveType(mixed $root): Type
    {
        if ($root instanceof stdClass && isset($root->good_boy)) {
            return GraphQL::type('Dog');
        }

        return GraphQL::type('Cat');
    }
}
