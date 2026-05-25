<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\CursorPaginationTest;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\User;

class UsersCursorPaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'Users with cursor pagination',
    ];

    public function type(): Type
    {
        return GraphQL::cursorPaginate('User');
    }

    public function args(): array
    {
        return [
            'take' => [
                'type' => Type::nonNull(Type::int()),
            ],
            'cursor' => [
                'type' => Type::string(),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo): CursorPaginator
    {
        return User::query()
            ->orderBy('id')
            ->cursorPaginate(
                perPage: $args['take'],
                cursorName: 'cursor',
                cursor: $args['cursor'] ?? null,
            );
    }
}
