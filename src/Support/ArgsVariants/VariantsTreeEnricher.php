<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ArgsVariants;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Executor\Values;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Introspection;

/**
 * Composes the legacy queryPlan() tree with an additive 'argsVariants' key
 * on field entries requested with ≥2 distinct normalized argument hashes
 * (see docs/superpowers/specs/2026-07-22-deferred-args-variants-design.md §1.1).
 *
 * Emission rule: variants are emitted when the RAW (directive-blind)
 * occurrences of a field at one tree position span ≥2 distinct argument
 * hashes AND ≥1 directive-ACTIVE occurrence remains; variant entries are
 * generated only for active occurrences.
 */
class VariantsTreeEnricher
{
    /**
     * @param array<string,mixed> $legacyTree Output of $info->lookAhead()->queryPlan()
     * @return array<string,mixed>
     */
    public function enrich(array $legacyTree, ResolveInfo $info): array
    {
        if (!$legacyTree) {
            // Nothing to enrich (e.g. a leaf/scalar field); also avoids
            // touching $info->fieldNodes for callers passing a minimal/stub
            // ResolveInfo (e.g. unit tests exercising resolver plumbing
            // without a real GraphQL execution).
            return $legacyTree;
        }

        $selectionSets = [];

        foreach ($info->fieldNodes as $fieldNode) {
            if (null !== $fieldNode->selectionSet) {
                $selectionSets[] = [$fieldNode->selectionSet, $info->fieldDefinition->getType()];
            }
        }

        if (!$selectionSets) {
            return $legacyTree;
        }

        return $this->enrichLevel($legacyTree, $selectionSets, $info);
    }

    /**
     * @param array<string,mixed> $legacyLevel The legacy-shape field map at this level
     * @param list<array{0:SelectionSetNode,1:Type}> $selectionSets Selection sets contributing to this level
     * @return array<string,mixed>
     */
    protected function enrichLevel(array $legacyLevel, array $selectionSets, ResolveInfo $info): array
    {
        $occurrences = [];

        foreach ($selectionSets as [$selectionSet, $parentType]) {
            $this->collectOccurrences($selectionSet, $parentType, $info, true, $occurrences);
        }

        foreach ($occurrences as $fieldName => $occs) {
            if (!isset($legacyLevel[$fieldName]) || !\is_array($legacyLevel[$fieldName])) {
                continue;
            }

            $active = array_values(array_filter($occs, static fn (array $o): bool => $o['active']));

            // Recurse into children along the merged path first, so nested
            // divergences under a non-conflicting ancestor are detected too.
            $childSets = [];

            foreach ($active as $o) {
                if (null !== $o['selectionSet']) {
                    $childSets[] = [$o['selectionSet'], $o['type']];
                }
            }

            if ($childSets && \is_array($legacyLevel[$fieldName]['fields'] ?? null) && $legacyLevel[$fieldName]['fields']) {
                $legacyLevel[$fieldName]['fields'] = $this->enrichLevel($legacyLevel[$fieldName]['fields'], $childSets, $info);
            }

            $rawHashes = array_unique(array_column($occs, 'hash'));

            if (\count($rawHashes) < 2 || !$active) {
                continue;
            }

            $variants = [];

            foreach ($active as $o) {
                $hash = $o['hash'];

                if (!isset($variants[$hash])) {
                    $variants[$hash] = [
                        'args' => $o['args'],
                        'fields' => [],
                        '_sets' => [],
                    ];
                }

                if (null !== $o['selectionSet']) {
                    $variants[$hash]['_sets'][] = [$o['selectionSet'], $o['type']];
                }
            }

            foreach ($variants as $hash => $variant) {
                $variants[$hash]['fields'] = $this->buildSubtree($variant['_sets'], $info);
                unset($variants[$hash]['_sets']);
            }

            $legacyLevel[$fieldName]['argsVariants'] = $variants;
        }

        return $legacyLevel;
    }

    /**
     * Build a legacy-shaped ('fieldName' => ['type','fields','args']) subtree
     * for one variant, merging its contributing selection sets and applying
     * the emission rule recursively (merge escalation).
     *
     * @param list<array{0:SelectionSetNode,1:Type}> $selectionSets
     * @return array<string,mixed>
     */
    protected function buildSubtree(array $selectionSets, ResolveInfo $info): array
    {
        $occurrences = [];

        foreach ($selectionSets as [$selectionSet, $parentType]) {
            $this->collectOccurrences($selectionSet, $parentType, $info, true, $occurrences);
        }

        $fields = [];

        foreach ($occurrences as $fieldName => $occs) {
            $active = array_values(array_filter($occs, static fn (array $o): bool => $o['active']));

            if (!$active) {
                continue;
            }

            $last = $active[\count($active) - 1];

            $childSets = [];

            foreach ($active as $o) {
                if (null !== $o['selectionSet']) {
                    $childSets[] = [$o['selectionSet'], $o['type']];
                }
            }

            $entry = [
                'type' => $last['type'],
                'fields' => $childSets ? $this->buildSubtree($childSets, $info) : [],
                'args' => $last['args'],
            ];

            $rawHashes = array_unique(array_column($occs, 'hash'));

            if (\count($rawHashes) >= 2) {
                $variants = [];

                foreach ($active as $o) {
                    $hash = $o['hash'];

                    if (!isset($variants[$hash])) {
                        $variants[$hash] = ['args' => $o['args'], 'fields' => [], '_sets' => []];
                    }

                    if (null !== $o['selectionSet']) {
                        $variants[$hash]['_sets'][] = [$o['selectionSet'], $o['type']];
                    }
                }

                foreach ($variants as $hash => $variant) {
                    $variants[$hash]['fields'] = $this->buildSubtree($variant['_sets'], $info);
                    unset($variants[$hash]['_sets']);
                }

                $entry['argsVariants'] = $variants;
            }

            $fields[$fieldName] = $entry;
        }

        return $fields;
    }

    /**
     * Collect raw occurrences of every field in a selection set, flattening
     * fragment spreads and inline fragments (mirroring how queryPlan() merges
     * them into one level), evaluating @skip/@include for the 'active' flag.
     *
     * @param array<string,list<array{args:array<string,mixed>,hash:string,active:bool,selectionSet:SelectionSetNode|null,type:Type}>> $occurrences
     */
    protected function collectOccurrences(
        SelectionSetNode $selectionSet,
        Type $parentType,
        ResolveInfo $info,
        bool $contextActive,
        array &$occurrences,
    ): void {
        $namedParent = Type::getNamedType($parentType);

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->name->value;

                if (Introspection::TYPE_NAME_FIELD_NAME === $fieldName) {
                    continue;
                }

                if (!$namedParent instanceof HasFieldsType || !$namedParent->hasField($fieldName)) {
                    // Union parents (and unknown fields) are out of scope for
                    // variants (spec non-goals); the legacy tree still covers them.
                    continue;
                }

                $fieldDef = $namedParent->getField($fieldName);
                $args = Values::getArgumentValues($fieldDef, $selection, $info->variableValues);

                $occurrences[$fieldName][] = [
                    'args' => $args,
                    'hash' => ArgsHasher::hash($args),
                    'active' => $contextActive && $this->isActive($selection, $info),
                    'selectionSet' => $selection->selectionSet,
                    'type' => $fieldDef->getType(),
                ];
            } elseif ($selection instanceof FragmentSpreadNode) {
                $fragment = $info->fragments[$selection->name->value] ?? null;

                if (null === $fragment) {
                    continue;
                }

                $typeCondition = $info->schema->getType($fragment->typeCondition->name->value);

                if (null === $typeCondition) {
                    continue;
                }

                $this->collectOccurrences(
                    $fragment->selectionSet,
                    $typeCondition,
                    $info,
                    $contextActive && $this->isActive($selection, $info),
                    $occurrences,
                );
            } elseif ($selection instanceof InlineFragmentNode) {
                $typeCondition = null === $selection->typeCondition
                    ? $parentType
                    : $info->schema->getType($selection->typeCondition->name->value);

                if (null === $typeCondition) {
                    continue;
                }

                $this->collectOccurrences(
                    $selection->selectionSet,
                    $typeCondition,
                    $info,
                    $contextActive && $this->isActive($selection, $info),
                    $occurrences,
                );
            }
        }
    }

    protected function isActive(Node $node, ResolveInfo $info): bool
    {
        $skip = Values::getDirectiveValues(Directive::skipDirective(), $node, $info->variableValues);

        if (true === ($skip['if'] ?? false)) {
            return false;
        }

        $include = Values::getDirectiveValues(Directive::includeDirective(), $node, $info->variableValues);

        if (null !== $include && false === ($include['if'] ?? true)) {
            return false;
        }

        return true;
    }
}
