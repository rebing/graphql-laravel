<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

abstract class Privacy
{
    public function fire(mixed ...$args): bool
    {
        return $this->validate(...$args);
    }

    /**
     * @param mixed $root The query root value, which is the result of the parent field's resolver
     * @param array<string, mixed> $fieldArgs The field's own declared arguments
     * @param mixed $queryContext The query context value
     *
     * @return bool Return `true` to allow access to the field in question,
     *              `false` otherwise
     */
    abstract public function validate($root, array $fieldArgs, $queryContext = null): bool;
}
