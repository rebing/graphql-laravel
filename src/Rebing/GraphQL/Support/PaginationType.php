<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationType extends ObjectType {

    public function __construct($typeName, $customName = null)
    {
        $name = $customName ?: $typeName . 'Pagination';

        $config = [
            'name'  => $name,
            'fields' => $this->getPaginationFields($typeName)
        ];

        parent::__construct($config);
    }

    protected function getPaginationFields($typeName)
    {
        return [
            'data' => [
                'type'          => GraphQLType::listOf(GraphQL::type($typeName)),
                'description'   => 'List of items on the current page',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->getCollection();  },
            ],
            'total' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Number of total items selected by the query',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->total(); },
                'selectable'    => false,
            ],
            'per_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Number of items returned per page',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->perPage(); },
                'selectable'    => false,
            ],
            'current_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Current page of the cursor',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->currentPage(); },
                'selectable'    => false,
            ],
            'from' => [
                'type'          => GraphQLType::int(),
                'description'   => 'Number of the first item returned',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->firstItem(); },
                'selectable'    => false,
            ],
            'to' => [
                'type'          => GraphQLType::int(),
                'description'   => 'Number of the last item returned',
                'resolve'       => function(LengthAwarePaginator $data) { return $data->lastItem(); },
                'selectable'    => false,
            ],
        ];
    }

}
