<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class MutationWithCustomRuleWithRuleObject extends Mutation
{
    protected $attributes = [
        'name' => 'mutationWithCustomRuleWithRuleObject',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function rules(array $args = [])
    {
        return [
            'arg1' => [
                'required',
                new RuleObject(),
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
