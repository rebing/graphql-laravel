<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class CrossFieldParentInputType extends InputType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'CrossFieldParentInput',
    ];

    public function fields(): array
    {
        return [
            'nested' => [
                'type' => GraphQL::type('CrossFieldRecipientInput'),
            ],
            'label' => [
                'type' => Type::string(),
            ],
        ];
    }
}
