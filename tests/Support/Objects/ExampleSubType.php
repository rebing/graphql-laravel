<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleSubType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ExampleSub',
    ];

    public function fields(): array
    {
        return [
            'field' => [
                'type' => Type::string(),
            ],
            'otherField' => [
                'type' => Type::string(),
            ],
        ];
    }
}
