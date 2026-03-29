<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\LazyTypeTests;

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

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $getSelectFields): mixed
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return User::query()
            ->select($selectFields->getSelect())
            ->with($selectFields->getRelations())
            ->get();
    }
}
