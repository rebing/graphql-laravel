<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsTest;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\User;

class UsersQuery extends Query
{
    /**
     * @var array<string,mixed>
     */
    protected $attributes = [
        'name' => 'users',
    ];

    public function args(): array
    {
        return [
            'name' => [
                Type::string(),
            ],
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

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @param mixed $ctx
     * @param ResolveInfo $resolveInfo
     * @param Closure $getSelectFields
     * @return Collection<int,mixed>
     */
    public function resolve($root, $args, $ctx, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
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

        if (isset($args['name']) && $args['name']) {
            $users->where('name', $args['name']);
        }

        $res = $users->orderBy('users.id')->get();

        return $res;
    }
}
