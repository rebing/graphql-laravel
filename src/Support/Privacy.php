<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

abstract class Privacy
{
    public function fire(): bool
    {
        $queryArgs = func_get_arg(0);
        $queryContext = func_get_arg(1);

        return $this->validate($queryArgs, $queryContext);
    }

    /**
     * @param array $queryArgs    Arguments given with the query/mutation
     * @param mixed $queryContext Context of the query/mutation
     *
     * @return bool Return `true` to allow access to the field in question,
     *   `false otherwise
     */
    abstract public function validate(array $queryArgs, $queryContext = null): bool;
}
