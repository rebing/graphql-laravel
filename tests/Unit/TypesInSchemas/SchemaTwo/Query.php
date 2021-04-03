<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\TypesInSchemas\SchemaTwo;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query as BaseQuery;

class Query extends BaseQuery
{
    protected $attributes = [
        'name' => 'query',
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('Type'));
    }

    public function resolve()
    {
        $result = new \stdClass();
        // Must match Type field
        $result->title = 'example from schema two';

        return $result;
    }
}
