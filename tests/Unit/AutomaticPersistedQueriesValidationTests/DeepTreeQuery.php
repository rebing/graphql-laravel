<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AutomaticPersistedQueriesValidationTests;

use GraphQL\Type\Definition\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use stdClass;

/**
 * Returns a tree of arbitrary depth — used by the depth-validation tests.
 */
class DeepTreeQuery extends Query
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'tree',
    ];

    public function type(): GraphQLType
    {
        return GraphQLType::nonNull(GraphQL::type('Tree'));
    }

    public function resolve(): stdClass
    {
        // Build five levels deep so the tests can assert against any
        // reasonable depth limit between 1 and 5.
        $node = new stdClass;
        $node->name = 'level-5';
        $node->child = null;

        for ($i = 4; $i >= 1; $i--) {
            $parent = new stdClass;
            $parent->name = 'level-' . $i;
            $parent->child = $node;
            $node = $parent;
        }

        return $node;
    }
}
