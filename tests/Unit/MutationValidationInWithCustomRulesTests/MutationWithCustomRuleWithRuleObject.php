<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\MutationValidationInWithCustomRulesTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class MutationWithCustomRuleWithRuleObject extends Mutation
{
    protected $attributes = [
        'name' => 'mutationWithCustomRuleWithRuleObject',
    ];

    public function type()
    {
        return Type::nonNull(Type::string());
    }

    public function rules(array $args = [])
    {
        return [
            'arg_in_rule_pass' => [
                'in:valid_name',
                new RuleObjectPass(),
            ],
            'arg_in_rule_fail' => [
                'in:valid_name',
                new RuleObjectFail(),
            ],
        ];
    }

    public function args()
    {
        return [
            'arg_in_rule_pass' => [
                'type' => Type::string(),
            ],
            'arg_in_rule_fail' => [
                'type' => Type::string(),
            ],
        ];
    }

    public function resolve($root, $args): string
    {
        return 'mutation result';
    }
}
