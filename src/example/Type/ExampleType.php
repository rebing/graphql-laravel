<?php

namespace example\Type;

use example\ExampleModel;
use Folklore\GraphQL\Support\Type as GraphQLType;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;

class ExampleType extends GraphQLType {

    protected $attributes = [
        'name'          => 'ExampleType',
        'description'   => 'An example of a type',
        'model'         => ExampleModel::class,
        'relations'     => [
            'xmpl'      => 'relation_example',
        ],
    ];

    public function fields()
    {
        return [
            'attributeA'    => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Random attribute',
            ],

            'xmpl'  => [
                'type'          => GraphQL::type($this->attributes['relations']['relation_example']),
                'description'   => 'Example relation',
            ],
        ];
    }

}