<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\WrapTypeTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Models\Post;

class WrapTypeQuery extends Query
{
    protected $attributes = [
        'name' => 'wrapTypeQuery',
    ];

    public function type(): Type
    {
        return GraphQL::wrapType(
            'Post',
            'PostWrapped',
            CustomWrapperType::class,
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $ctx, ResolveInfo $info, Closure $getSelectFields): LengthAwarePaginator
    {
        /** @var SelectFields $selectFields */
        $selectFields = $getSelectFields();

        return Post::with($selectFields->getRelations())
            ->select($selectFields->getSelect())
            ->paginate(1);
    }
}
