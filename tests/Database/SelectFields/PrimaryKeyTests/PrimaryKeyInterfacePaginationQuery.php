<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests\PrimaryKeyPaginationQuery;
use Rebing\GraphQL\Tests\Support\Models\Post;

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
