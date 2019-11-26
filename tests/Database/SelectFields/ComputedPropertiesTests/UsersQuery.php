<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ComputedPropertiesTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\User;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function resolve($root, $args, $contxt, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return User
            ::select($selectFields->getSelect())
            ->with($selectFields->getRelations())
            ->get();
    }
}
