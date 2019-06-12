<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class MutationWithCustomRuleWithClosure extends Mutation
{
    protected $attributes = [
        'name' => 'mutationWithCustomRuleWithClosure',
    ];

    public function type()
    {
        return Type::nonNull(Type::string());
    }

    public function rules(array $args = [])
    {
        return [
            'arg1' => [
                'required',
                function (string $attribute, $value, $fail) {
                    $fail($attribute.' is invalid');
                },
            ],
        ];
    }

    public function args()
    {
        return [
            'arg1' => [
                'type' => Type::string(),
            ],
        ];
    }

    public function resolve($root, $args): string
    {
        return 'mutation result';
    }
}
