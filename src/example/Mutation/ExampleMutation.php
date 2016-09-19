<?php

namespace example\Mutation;

use example\ExampleModel;
use Rebing\GraphQL\Support\Mutation;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;

class ExampleMutation extends Mutation {

    protected $attributes = [
        'name'          => 'Example',
        'description'   => 'Change a random attribute',
    ];

    public function type()
    {
        return GraphQL::type('example');
    }

    public function args()
    {
        return [
            'id'    => [
                'name'  => 'primary key',
                'type'  => Type::nonNull(Type::int()),
                'rules' => ['required', 'integer'],
            ],
            'attribute' => [
                'name'  => 'random attribute',
                'type'  => Type::nonNull(Type::string()),
                'rules' => ['required', 'string'],
            ],
        ];
    }

    public function resolve($root, $args)
    {
        $example = ExampleModel::where('id', '=', $args['id']);

        $example->update(['attribute' => $args['attribute']]);

        return $example;
    }

}