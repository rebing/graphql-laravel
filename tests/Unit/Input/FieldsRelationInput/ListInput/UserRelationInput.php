<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input\FieldsRelationInput\ListInput;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class UserRelationInput extends InputType
{
    /** @var array<string,mixed> */
    protected $attributes = [
        'name' => 'UserRelationInput',
    ];

    public function fields(): array
    {
        return [
            'input_type' => [
                'type' => Type::string(),
                'rules' => [
                    'filled',
                    'prohibits:another_input_type',
                ],
            ],
            'another_input_type' => [
                'type' => Type::string(),
            ],
        ];
    }
}
