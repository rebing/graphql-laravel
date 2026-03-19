<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

/**
 * A mutation that disables automatic cross-field rule prefixing
 * by overriding processCollectedRules() as a no-op.
 */
class CrossFieldDisabledPrefixingMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'crossFieldDisabledPrefixing',
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
        ];
    }

    /**
     * Override to disable automatic rule prefixing (escape hatch).
     *
     * @param array<string,mixed> $rules
     * @return array<string,mixed>
     */
    protected function processCollectedRules(array $rules): array
    {
        return $rules;
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
