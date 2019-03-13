<?php

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class ExamplesFilteredQuery extends Query
{
    protected $attributes = [
        'name' => 'Filtered examples'
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type('Example'));
    }

    public function args()
    {
        return [
            'filter' => [
                'name' => 'filter', 
                'type' => GraphQL::type('ExampleFilterInput')
            ]
        ];
    }

    public function resolve($root, $args)
    {
        $data = include(__DIR__.'/data.php');
        $result = [];

        if (isset($args['filter'])) {
            if(isset($args['filter']['test'])) {
                foreach($data as $item) {
                    if($item['test'] == $args['filter']['test']) {
                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }
}
