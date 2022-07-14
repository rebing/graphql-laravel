<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input\FieldsRelationInput\ListInput;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UserRelationUpdateMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'userRelationUpdate',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::boolean());
    }

    public function args(): array
    {
        return [
            'data' => [
                'name' => 'data',
                'type' => Type::listOf(GraphQL::type('UserRelationInput')),
                'rules' => [
                    'required',
                ],
            ],
        ];
    }

    public function resolve(): bool
    {
        return true;
    }
}
