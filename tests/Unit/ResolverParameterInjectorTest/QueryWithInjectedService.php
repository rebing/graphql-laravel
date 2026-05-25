<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ResolverParameterInjectorTest;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class QueryWithInjectedService extends Query
{
    protected $attributes = [
        'name' => 'queryWithInjectedService',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo, InjectableService $service): string
    {
        return $service->marker;
    }
}
