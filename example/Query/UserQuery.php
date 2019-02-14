<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\GraphQL;
use Rebing\GraphQL\Support\SelectFields;
use Models\User; // not included in this project

class UserQuery extends Query {

    use Authenticate;

    protected $attributes = [
        'name'  => 'Users',
    ];

    public function type()
    {
        return GraphQL::type('user');
    }

    public function args()
    {
        return [
            'id'    => [
                'name' => 'id',
                'type' => Type::int(),
            ],
        ];
    }

    public function resolve($root, $args, SelectFields $fields)
    {
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        return User::where('id', '=', $args['id'])->with($with)->select($select)->first();
    }

}