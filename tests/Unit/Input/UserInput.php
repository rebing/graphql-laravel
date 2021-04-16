<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class UserInput extends InputType
{
    /** @var array<string,mixed> */
    protected $attributes = [
        'name' => 'UserInput',
    ];

    public function fields(): array
    {
        return [
            'password' => [
                'name' => 'password',
                'type' => Type::string(),
                'rules' => [
                    'string',
                    'nullable',
                    'same:data.*.password_confirmation',
                ],
            ],
            'password_confirmation' => [
                'name' => 'password_confirmation',
                'type' => Type::string(),
            ],
        ];
    }
}
