<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\AliasAguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateExampleMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateExample',
    ];

    public function type(): Type
    {
        return GraphQL::type(ExampleType::TYPE);
    }

    public function args(): array
    {
        return [
            'test' => [
                'alias' => 'test_alias',
                'type'  => Type::string(),
            ],
            'test_with_alias_and_null' => [
                'type'         => Type::string(),
                'defaultValue' => 'NULL SHOULD NOT ALIAS',
            ],
            'test_has_default_value' => [
                'type'         => Type::string(),
                'defaultValue' => 'DefaultValue123',
            ],
            'test_type' => [
                'type' => GraphQL::type(ExampleValidationInputObject::TYPE),
            ],
            'test_type_duplicates' => [
                'type' => GraphQL::type(ExampleValidationInputObject::TYPE),
            ],
            'a_list' => [
                'type' => Type::listOf(GraphQL::type(ExampleNestedValidationInputObject::TYPE)),
            ],
            'a_list_non_null' => [
                'type' => Type::nonNull(Type::listOf(GraphQL::type(ExampleNestedValidationInputObject::TYPE))),
            ],
            'a_list_non_null_and_type_nonNull' => [
                'type' => Type::nonNull(
                    Type::listOf(Type::nonNull(GraphQL::type(ExampleNestedValidationInputObject::TYPE)))
                ),
            ],
            'a_list_type_nonNull' => [
                'type' => Type::listOf(
                    Type::nonNull(GraphQL::type(ExampleNestedValidationInputObject::TYPE))
                ),
            ],

        ];
    }

    public function resolve($root, $args)
    {
        return [
            'test' => json_encode($args),
        ];
    }
}
