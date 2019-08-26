<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Query\User;

use Models\User;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;

class UsersQuery extends Query
{
    protected $attributes = [
        'name'  => 'Users',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args(): array
    {
        return [
            'ids'   => [
                'name' => 'ids',
                'type' => Type::listOf(Type::int()),
            ],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        return User::where('id', '=', $args['id'])->with($with)->select($select)->get();
    }
}
