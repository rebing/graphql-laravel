<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostWithSelectFieldsAndModelAndAliasQuery extends Query
{
    protected $attributes = [
        'name' => 'postWithSelectFieldsAndModelAndAlias',
    ];

    public function type(): Type
    {
        return GraphQL::type('PostWithModelAndAlias');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Post::select($getSelectFields()->getSelect())
            ->findOrFail($args['id']);
    }
}
