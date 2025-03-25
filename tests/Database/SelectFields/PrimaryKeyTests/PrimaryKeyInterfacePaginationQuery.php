<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PrimaryKeyInterfacePaginationQuery extends PrimaryKeyPaginationQuery
{
    protected $attributes = [
        'name' => 'primaryKeyInterfacePaginationQuery',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('ModelInterface');
    }
}
