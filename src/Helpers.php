<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Closure;

class Helpers
{
    /**
     * Originally from \Nuwave\Lighthouse\Support\Utils::applyEach
     *
     * Apply a callback to a value or each value in an array.
     *
     * @param mixed|array<mixed> $valueOrValues
     * @return mixed|array<mixed>
     */
    public static function applyEach(Closure $callback, $valueOrValues)
    {
        if (is_array($valueOrValues)) {
            return array_map($callback, $valueOrValues);
        }

        return $callback($valueOrValues);
    }
}
