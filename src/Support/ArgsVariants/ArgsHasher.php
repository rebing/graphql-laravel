<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ArgsVariants;

/**
 * Deterministic hash of a resolved GraphQL argument set.
 *
 * Both the tree enrichment (build time) and the select-fields variant
 * resolver (runtime) must compute identical hashes for identical argument
 * values, so normalization must be stable: associative keys are sorted
 * recursively, list order is preserved (positional semantics).
 */
final class ArgsHasher
{
    /** @param array<string,mixed> $args */
    public static function hash(array $args): string
    {
        return md5(serialize(self::normalize($args)));
    }

    private static function normalize(mixed $value): mixed
    {
        if (!\is_array($value)) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }

            if ($value instanceof \UnitEnum) {
                return $value->name;
            }

            return $value;
        }

        $isList = array_is_list($value);
        $value = array_map(static fn (mixed $v): mixed => self::normalize($v), $value);

        if (!$isList) {
            ksort($value);
        }

        return $value;
    }
}
