<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use Closure;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Illuminate\Pagination\LengthAwarePaginator;

class ExamplesPaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'Examples with pagination',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Example');
    }

    public function args(): array
    {
        return [
            'take' => [
                'type' => Type::nonNull(Type::int()),
            ],
            'page' => [
                'type' => Type::nonNull(Type::int()),
            ],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): LengthAwarePaginator
    {
        $data = include __DIR__.'/data.php';

        $take = $args['take'];
        $page = $args['page'] - 1;

        return new LengthAwarePaginator(
            collect($data)->slice($page * $take, $take),
            count($data),
            $take,
            $page
        );
    }
}
