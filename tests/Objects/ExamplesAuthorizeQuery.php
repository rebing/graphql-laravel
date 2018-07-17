<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class ExamplesAuthorizeQuery extends Query
{
    protected $attributes = [
        'name' => 'Examples authorize query'
    ];

    public function authorize(array $args)
    {
        return false;
    }

    public function type()
    {
        return Type::listOf(GraphQL::type('Example'));
    }

    public function args()
    {
        return [
            'index' => ['name' => 'index', 'type' => Type::int()]
        ];
    }

    public function resolve($root, $args)
    {
        $data = include(__DIR__.'/data.php');

        if (isset($args['index'])) {
            return [
                $data[$args['index']]
            ];
        }

        return $data;
    }
}
