<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class ExamplesPaginationWithStrictDataQuery extends Query
{
    protected $attributes = [
        'name' => 'Examples with pagination of strict data',
    ];

    public function type(): Type
    {
        return GraphQL::paginate(Type::nonNull(GraphQL::type('ExampleWithSubtype'))->toString());
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

    public function resolve(
        $root,
        $args,
        $context,
        ResolveInfo $resolveInfo,
        SelectFields $getSelectFields
    ): LengthAwarePaginator {
        $data = include __DIR__ . '/data_with_sub.php';

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
