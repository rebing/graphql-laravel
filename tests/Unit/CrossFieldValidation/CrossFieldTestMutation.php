<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CrossFieldTestMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'crossFieldTest',
    ];

    public function type(): Type
    {
        return Type::string();
    }

    public function args(): array
    {
        return [
            'recipients' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('CrossFieldRecipientInput')))),
                'rules' => ['required'],
            ],
            'conditionalItems' => [
                'type' => Type::listOf(Type::nonNull(GraphQL::type('CrossFieldConditionalInput'))),
            ],
            'singleRecipient' => [
                'type' => GraphQL::type('CrossFieldRecipientInput'),
            ],
            'deepNested' => [
                'type' => GraphQL::type('CrossFieldParentInput'),
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, array $args): string
    {
        return 'ok';
    }
}
