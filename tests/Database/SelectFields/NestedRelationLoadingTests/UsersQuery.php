<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\NestedRelationLoadingTests;

use Closure;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\User;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function args(): array
    {
        return [
            'select' => [
                'type' => Type::boolean(),
            ],
            'with' => [
                'type' => Type::boolean(),
            ],
        ];
    }

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('User'))));
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $users = User::query();

        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        if (isset($args['select']) && $args['select']) {
            $users->select($selectFields->getSelect());
        }

        if (isset($args['with']) && $args['with']) {
            $users->with($selectFields->getRelations());
        }

        return $users->orderBy('users.id')->get();
    }
}
