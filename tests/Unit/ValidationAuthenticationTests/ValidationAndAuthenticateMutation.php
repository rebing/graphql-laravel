<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthenticationTests;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class ValidationAndAuthenticateMutation extends Mutation
{
    protected $attributes = [
        'name' => 'validationAndAuthentication',
    ];

    public function authenticate($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return 'value1' === $args['arg1'];
    }

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'arg1' => [
                'type' => Type::string(),
                'rules' => 'in:value1',
            ],
        ];
    }

    public function resolve(): string
    {
        return 'value';
    }
}
