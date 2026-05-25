<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ResolverParameterInjectorTest;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Rebing\GraphQL\Support\Query;

class QueryWithBuiltInTypedParam extends Query
{
    protected $attributes = [
        'name' => 'queryWithBuiltInTypedParam',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    /**
     * Built-in types (e.g. `string`, `int`) cannot be auto-injected and must
     * trigger an InvalidArgumentException.
     *
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo, string $cannotInject = 'x'): string
    {
        throw new InvalidArgumentException('Should never run; injection should have failed before.');
    }
}
