<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationType extends ObjectType
{
    public function __construct($typeName, $customName = null)
    {
        $paginationTypeName = $this->getPaginationTypeName($typeName, $customName);

        $config = $this->getConfig($paginationTypeName);

        parent::__construct($config);
    }

    public function getPaginationTypeName($typeName, $customName)
    {
        return $customName ?: $typeName.'Pagination';
    }

    public function getPaginationFields()
    {
        return [
            'data' => [
                'type'          => GraphQLType::listOf(GraphQL::type($typeName)),
                'description'   => 'List of items on the current page',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->getCollection();
                },
            ],
            'count' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Number of current paginated items selected by the query',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->count();
                },
                'selectable'    => false,
            ],
            'total' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Number of total items selected by the query',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->total();
                },
                'selectable'    => false,
            ],
            'per_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Number of items returned per page',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->perPage();
                },
                'selectable'    => false,
            ],
            'current_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Current page of the cursor',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->currentPage();
                },
                'selectable'    => false,
            ],
            'last_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Last page for the cursor',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->lastPage();
                },
                'selectable'    => false,
            ],
            'from' => [
                'type'          => GraphQLType::int(),
                'description'   => 'Number of the first item returned',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->firstItem();
                },
                'selectable'    => false,
            ],
            'to' => [
                'type'          => GraphQLType::int(),
                'description'   => 'Number of the last item returned',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->lastItem();
                },
                'selectable'    => false,
            ],
        ];
    }

    public function getConfig($typeName)
    {
        return [
            'name'   => $typeName,
            'fields' => $this->getPaginationFields(),
        ];
    }
}
