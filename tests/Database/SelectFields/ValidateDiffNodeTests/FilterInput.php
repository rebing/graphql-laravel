<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateDiffNodeTests;

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
            'body' => [
                'type' => Type::string(),
            ],
            'id' => [
                'type' => Type::int(),
            ],
            'title' => [
                'type' => Type::string(),
            ],
        ];
    }
}
