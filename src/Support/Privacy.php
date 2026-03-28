<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ResolveInfo;

abstract class Privacy
{
    public function fire(mixed ...$args): bool
    {
        return $this->validate(...$args);
    }

    /**
     * @param mixed $root The root value for the field resolver (the parent object)
     * @param array<string, mixed> $fieldArgs The field's own declared arguments
     * @param mixed $queryContext The query context value
     * @param ResolveInfo|null $resolveInfo The GraphQL resolve info
     *
     * @return bool Return `true` to allow access to the field in question,
     *              `false` otherwise
     */
    abstract public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool;
}
