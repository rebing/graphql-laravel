<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AutomaticPersistedQueriesValidationTests;

use GraphQL\Type\Definition\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

/**
 * A self-referential `Tree` type used to construct queries of arbitrary
 * depth in the depth-validation tests. Each `Tree` has a `name` and an
 * optional `child` of the same type.
 */
class TreeType extends Type
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'Tree',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => GraphQLType::nonNull(GraphQLType::string()),
            ],
            'child' => [
                'type' => GraphQL::type('Tree'),
            ],
        ];
    }
}
