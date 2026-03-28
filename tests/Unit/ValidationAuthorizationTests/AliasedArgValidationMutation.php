<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthorizationTests;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

/**
 * Mutation with an aliased argument and a validation rule on the original name.
 *
 * Used to verify that validation runs on the pre-alias argument names,
 * not the aliased names that the resolver receives.
 */
class AliasedArgValidationMutation extends Mutation
{
    protected $attributes = [
        'name' => 'aliasedArgValidation',
    ];

    /**
     * @param array<string,mixed> $args
     */
    public function authorize($root, array $args, $ctx, ?ResolveInfo $resolveInfo = null): bool
    {
        return true;
    }

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'test_arg' => [
                'type' => Type::string(),
                'alias' => 'test_alias',
                'rules' => ['required', 'in:valid_value'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args): string
    {
        // If alias mapping works correctly, resolve sees 'test_alias'
        return $args['test_alias'] ?? 'ALIAS_KEY_MISSING';
    }
}
