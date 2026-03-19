<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class CrossFieldConditionalInputType extends InputType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'CrossFieldConditionalInput',
    ];

    public function fields(): array
    {
        return [
            'mode' => [
                'type' => Type::string(),
                'rules' => ['required'],
            ],
            'advancedConfig' => [
                'type' => Type::string(),
                'rules' => ['required_if:mode,advanced'],
            ],
        ];
    }
}
