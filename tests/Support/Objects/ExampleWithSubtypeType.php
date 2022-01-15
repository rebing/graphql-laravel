<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleWithSubtypeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ExampleWithSubtype',
    ];

    public function fields(): array
    {
        return [
            'field' => [
                'type' => Type::string(),
            ],
            'other_field' => [
                'type' => Type::string(),
            ],
            'sub' => [
                'type' => GraphQL::type('ExampleSub'),
            ],
        ];
    }
}
