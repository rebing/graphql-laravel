<?php

declare(strict_types = 1);
namespace App\GraphQL\Queries;

use App\Models\User;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UserQuery extends Query
{
    protected $attributes = [
        'name' => 'User',
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo)
    {
        return User::findOrFail($args['id']);
    }
}
