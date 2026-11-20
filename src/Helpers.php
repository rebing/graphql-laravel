<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Closure;
use RuntimeException;

class Helpers
{
    /**
     * @internal
     * @param array<int|string,mixed>|null $matches
     * @param-out array<int|string,string> $matches
     */
    public static function preg_match(string $pattern, string $subject, ?array &$matches = null): bool
    {
        $result = @preg_match($pattern, $subject, $matches);

        if (false === $result) {
            throw new RuntimeException('preg_match failed: ' . preg_last_error_msg());
        }

        return 1 === $result;
    }

    /**
     * @internal
     */
    public static function stripSuffix(string $value, string $suffix): string
    {
        return '' !== $suffix && str_ends_with($value, $suffix)
            ? substr($value, 0, -\strlen($suffix))
            : $value;
    }

    /**
     * Originally from \Nuwave\Lighthouse\Support\Utils::applyEach
     *
     * Apply a callback to a value or each value in an array.
     *
     * @param mixed|list<mixed> $valueOrValues
     * @return mixed|list<mixed>
     */
    public static function applyEach(Closure $callback, $valueOrValues)
    {
        if (\is_array($valueOrValues)) {
            return array_map($callback, $valueOrValues);
        }

        return $callback($valueOrValues);
    }
}
