<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class ExamplesQuery extends Query
{
    protected $attributes = [
        'name' => 'examples'
    ];

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
