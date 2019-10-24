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

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    protected function rules(array $args = []): array
    {
        return [
            'arg1' => [
                'required',
                function (string $attribute, $value, $fail) {
                    $fail($attribute . ' is invalid');
                },
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

    public function resolve($root, $args): string
    {
        return 'mutation result';
    }
}
