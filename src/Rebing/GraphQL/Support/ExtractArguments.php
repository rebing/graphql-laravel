<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Executor\Values;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;

class ExtractArguments
{
    private $resolveInfo;

    public function __construct(ResolveInfo $resolveInfo)
    {
        $this->resolveInfo = $resolveInfo;
    }

    public function forKey(string $key): array
    {
        $type = $this->resolveInfo->returnType;

        if (is_subclass_of($type, ScalarType::class)) {
            return [];
        }

        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        $selections = $this->resolveInfo->fieldNodes[0]->selectionSet->selections;

        $relevantNode = collect($selections)->first(function (FieldNode $node) use ($key) {
            return $node->name->value === $key;
        });

        return Values::getArgumentValues(
            $type->getField($relevantNode->name->value),
            $relevantNode,
            $this->resolveInfo->variableValues
        );
    }
}
