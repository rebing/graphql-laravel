<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

/**
 * Example OneOf Input Type for testing.
 * Allows searching by exactly one criteria.
 */
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
                'description' => 'Search by ID',
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
