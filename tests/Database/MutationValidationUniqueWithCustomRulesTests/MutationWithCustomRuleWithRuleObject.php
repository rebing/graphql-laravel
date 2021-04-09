<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\MutationValidationUniqueWithCustomRulesTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class MutationWithCustomRuleWithRuleObject extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'mutationWithCustomRuleWithRuleObject',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    protected function rules(array $args = []): array
    {
        return [
            'arg_unique_rule_pass' => [
                'unique:users,name',
                new RuleObjectPass(),
            ],
            'arg_unique_rule_fail' => [
                'unique:users,name',
                new RuleObjectFail(),
            ],
        ];
    }

    public function args(): array
    {
        return [
            'arg_unique_rule_pass' => [
                'type' => Type::string(),
            ],
            'arg_unique_rule_fail' => [
                'type' => Type::string(),
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, array $args): string
    {
        return 'mutation result';
    }
}
