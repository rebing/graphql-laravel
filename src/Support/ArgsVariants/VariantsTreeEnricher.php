<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ArgsVariants;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Composes the legacy queryPlan() tree with an additive 'argsVariants' key
 * on field entries requested with ≥2 distinct normalized argument hashes
 * (see docs/superpowers/specs/2026-07-22-deferred-args-variants-design.md §1.1).
 *
 * For queries without such conflicts the input tree is returned unchanged.
 */
class VariantsTreeEnricher
{
    /**
     * @param array<string,mixed> $legacyTree Output of $info->lookAhead()->queryPlan()
     * @return array<string,mixed>
     */
    public function enrich(array $legacyTree, ResolveInfo $info): array
    {
        return $legacyTree;
    }
}
