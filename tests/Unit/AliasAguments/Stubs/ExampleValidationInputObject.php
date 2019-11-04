<?php

namespace Rebing\GraphQL\Tests\Unit\AliasAguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class ExampleValidationInputObject extends InputType
{
    public const TYPE = 'ExampleValidationInputObject';

    protected $attributes = [
        'name' => self::TYPE,
    ];

    public function fields(): array
    {
        return [
            'val' => [
                'type' => Type::int(),
                'alias' => 'val_alias',
            ],
            'defaultValue' => [
                'type' => Type::int(),
                'alias' => 'defaultValue_alias',
                'defaultValue' => 'def',
            ],

            'nest' => [
                'type' => GraphQL::type(ExampleNestedValidationInputObject::TYPE),
            ],
            'list' => [
                'type' => Type::listOf(GraphQL::type(ExampleNestedValidationInputObject::TYPE)),
            ],
        ];
    }
}
