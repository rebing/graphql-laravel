<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

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
            'arg1' => [
                'required',
                new RuleObject(),
            ],
        ];
    }

    public function args(): array
    {
        return [
            'arg1' => [
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
