<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationType extends ObjectType {

    public function __construct($typeName)
    {
        $config = [
            'name'  => $typeName . '_pagination',
            'fields' => array_merge($this->getPaginationFields(), [
                'data' => [
                    'type' => Type::listOf(GraphQL::type($typeName)),
                ],
            ])
        ];

        parent::__construct($config);
    }

    protected function getPaginationFields()
    {
        return [
            'total' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Number of total items selected by the query',
                'selectable'    => false,
            ],
            'per_page' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Number of items returned per page',
                'selectable'    => false,
            ],
            'current_page' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Current page of the cursor',
                'selectable'    => false,
            ],
            'from' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Number of the first item returned',
                'selectable'    => false,
            ],
            'to' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Number of the last item returned',
                'selectable'    => false,
            ],
        ];
    }

}