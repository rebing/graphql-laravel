<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SearchInputTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class SearchInput extends InputType
{
    protected $attributes = [
        'name' => 'SearchInput',
        'description' => 'Search by exactly one criteria',
        'isOneOf' => true,
    ];

    public function fields(): array
    {
        return [
            'byId' => [
                'type' => Type::id(),
                'description' => 'Search by user ID',
            ],
            'byEmail' => [
                'type' => Type::string(),
                'description' => 'Search by email address',
            ],
            'byUsername' => [
                'type' => Type::string(),
                'description' => 'Search by username',
            ],
        ];
    }
}
