<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input\FieldsRelationInput\RootInput;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class UserRelationRootUpdateMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'userRelationRootUpdate',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::boolean());
    }

    public function args(): array
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

    public function resolve(): bool
    {
        return true;
    }
}
