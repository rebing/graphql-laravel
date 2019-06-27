<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use GraphQL;

class PaginationCursorType extends ObjectType
{
    public function __construct()
    {
        // See https://laravel.com/api/5.6/Illuminate/Pagination/LengthAwarePaginator.html for more fields.
        parent::__construct([
            'name' => 'PaginationCursor',
            'fields' => [
                'total' => [
                    'type' => GraphQLType::nonNull(GraphQLType::int()),
                    'resolve' => function (LengthAwarePaginator $paginator) {
                        return $paginator->total();
                    },
                ],
                'perPage' => [
                    'type' => GraphQLType::nonNull(GraphQLType::int()),
                    'resolve' => function (LengthAwarePaginator $paginator) {
                        return $paginator->perPage();
                    },
                ],
                'currentPage' => [
                    'type' => GraphQLType::nonNull(GraphQLType::int()),
                    'resolve' => function (LengthAwarePaginator $paginator) {
                        return $paginator->currentPage();
                    },
                ],
                'hasPages' => [
                    'type' => GraphQLType::nonNull(GraphQLType::boolean()),
                    'resolve' => function (LengthAwarePaginator $paginator) {
                        return $paginator->hasPages();
                    },
                ],
            ],
        ]);
    }
}
