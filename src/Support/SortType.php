<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\InputObjectType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type as GraphQLType;
use Rebing\GraphQL\Type\Definition\DirectionEnumType;

class SortType extends InputObjectType
{
    public function __construct(string $typeName, string $customName = null)
    {
        $name = $customName ?: $typeName.'SortType';

        $config = [
            'name' => $name,
            'fields' => $this->getSortFields($typeName)
        ];

        parent::__construct($config);
    }

    protected function getSortFields($typeName): array
    {
        return [
            [
                'name' => 'field',
                'type' => GraphQL::type($typeName),
                'description' => 'List of fields',
            ],
            [
                'name' => 'direction',
                'type' => new DirectionEnumType([
                    DirectionEnumType::TYPE_NAME
                ]),
                'description' => 'Sorting direction'
            ]
        ];
    }
}
