<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\NestedRelationLoadingTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class FilterInput extends InputType
{
    protected $attributes = [
        'name' => 'Filter',
        'description' => 'filter object',
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::string(),
            ],
            'body' => [
                'type' => Type::string(),
            ],
            'keywords' => [
                'type' => Type::listOf(Type::string()),
            ],
        ];
    }
}
