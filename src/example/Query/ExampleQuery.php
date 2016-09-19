<?php

namespace example\Query;

use example\ExampleModel;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use Auth;

class ExampleQuery extends Query {

    public function authorize(array $args)
    {
        if(isset($args['id']))
        {
            return Auth::id() == $args['id'];
        }

        return true;
    }

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