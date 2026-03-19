<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class CrossFieldRecipientInputType extends InputType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'CrossFieldRecipientInput',
    ];

    public function fields(): array
    {
        return [
            'createParams' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'prohibits:mintParams'],
            ],
            'mintParams' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'prohibits:createParams'],
            ],
            'email' => [
                'type' => Type::string(),
                'rules' => ['required_without:phone'],
            ],
            'phone' => [
                'type' => Type::string(),
                'rules' => ['required_without:email'],
            ],
        ];
    }
}
