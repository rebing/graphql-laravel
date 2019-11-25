<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\AliasAguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class ExampleNestedValidationInputObject extends InputType
{
    public const TYPE = 'ExampleNestedValidationInputObject';

    protected $attributes = [
        'name' => self::TYPE,
    ];

    public function fields(): array
    {
        return [
            'email' => [
                'type'  => Type::string(),
                'alias' => 'email_alias',
            ],
            'defaultField' => [
                'type'         => Type::string(),
                'alias'        => 'default_field_alias',
                'defaultValue' => 'defcon',
            ],
            'defaultFieldZeroLengthString' => [
                'type'         => Type::string(),
                'alias'        => 'default_field_zero_string',
                'defaultValue' => '',
            ],
        ];
    }
}
