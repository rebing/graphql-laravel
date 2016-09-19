<?php

namespace example\Type;

use example\ExampleRelationModel;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;

class ExampleRelationType extends GraphQLType {

    protected $attributes = [
        'name'          => 'RelationType',
        'description'   => 'An example of a relation type',
        'model'         => ExampleRelationModel::class,
    ];

    public function fields()
    {
        return [
            'random'    => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Random attribute',
            ],
        ];
    }

}