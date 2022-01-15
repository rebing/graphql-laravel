<?php

namespace Rebing\GraphQL\Tests\Support\Types\Wrappers;

use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\PaginationType as OriginPaginationType;

class PaginationType extends OriginPaginationType
{
    protected function getPaginationFields(string $typeName): array
    {
        return [
            'data' => [
                'type' => Type::nonNull(Type::listOf(GraphQL::type($typeName))),
                'resolve' => function (LengthAwarePaginator $data) {
                    return $data->items();
                },
            ],
            'cursor' => [
                'type' => Type::nonNull(PaginationMeta::type($typeName)),
                'selectable' => false,
                'resolve' => function (LengthAwarePaginator $data) {
                    return $data;
                },
            ],
        ];
    }
}
