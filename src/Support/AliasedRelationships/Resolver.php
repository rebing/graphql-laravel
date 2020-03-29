<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\AliasedRelationships;

use GraphQL\Type\Definition\ResolveInfo;

class Resolver
{
    /**
     * @param mixed $type
     * @param array<string,mixed> $args
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     */
    public function __invoke($type, $args, $context, ResolveInfo $resolveInfo)
    {
        $name = $resolveInfo->fieldName;
        $alias = null;
        if (isset($resolveInfo->fieldNodes[0]->alias)) {
            $alias = GenerateRelationshipKey::generate($resolveInfo->fieldNodes[0]->alias->value);
        }

        return $type->{$alias ?: $name};
    }
}
