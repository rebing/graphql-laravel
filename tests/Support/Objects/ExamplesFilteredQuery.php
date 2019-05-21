<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ExamplesFilteredQuery extends Query
{
    protected $attributes = [
        'name' => 'Filtered examples',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Example'));
    }

    public function args(): array
    {
        return [
            'filter' => [
                'name' => 'filter',
                'type' => GraphQL::type('ExampleFilterInput'),
            ],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, SelectFields $selectFields): array
    {
        $data = include __DIR__.'/data.php';
        $result = [];

        if (isset($args['filter'])) {
            if (isset($args['filter']['test'])) {
                foreach ($data as $item) {
                    if ($item['test'] == $args['filter']['test']) {
                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }
}
