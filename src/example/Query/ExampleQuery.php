<?php

namespace example\Query;

use example\ExampleModel;
use Folklore\GraphQL\Support\Query;
use Folklore\GraphQL\Support\SelectFields;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;

class ExampleQuery extends Query {

    protected $attributes = [
        'name'  => 'Example query',
    ];

    public function type()
    {
        return GraphQL::type('example');
    }

    public function args()
    {
        return [
            'id'   => ['name' => 'example primary attribute', 'type' => Type::int()],
        ];
    }

    public function resolve($root, $args, SelectFields $fields)
    {
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        if(isset($args['id']))
        {
            return ExampleModel::where('id', '=', $args['id'])->with($with)->select($select);
        }

        return ExampleModel::all();
    }

}