<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UnionTypeTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use stdClass;

class AnimalsQuery extends Query
{
    protected $attributes = [
        'name' => 'animals',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Animal'))));
    }

    /**
     * @return list<stdClass>
     */
    public function resolve(): array
    {
        $cat = new stdClass;
        $cat->name = 'Whiskers';
        $cat->meow_volume = 7;

        $dog = new stdClass;
        $dog->name = 'Rex';
        $dog->good_boy = true;

        return [$cat, $dog];
    }
}
