<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectFieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\ResolveInfo;
use RuntimeException;

/**
 * This adapts \GraphQL\Type\Definition\ResolveInfo::getFieldSelection
 * but with support for both field *and* arguments.
 */
class ResolveInfoFieldsAndArguments
{
    /** @var ResolveInfo */
    public $info;

    public function __construct(ResolveInfo $info)
    {
        $this->info = $info;
    }

    /**
     * Helper method that returns names of all fields with attributes selected in query for
     * $this->fieldName up to $depth levels.
     *
     * Example:
     * query MyQuery{
     * {
     *   root {
     *     nested(input:value) {
     *      nested1
     *      nested2 {
     *        nested3(input:value)
     *      }
     *     }
     *   }
     * }
     *
     * Given this ResolveInfo instance is a part of "root" field resolution, and $depth === 1,
     * method will return:
     * [
     *      'nested' => [
     *          'args' => [
     *              'input' => 'value',
     *          ],
     *          'fields' => [
     *              'nested1' => [
     *                  'args' => [],
     *                  'fields' => true,
     *              ],
     *              'nested2' => [
     *                  'args' => [],
     *                  'fields' => [
     *                      'nested3' => [
     *                          'args' => [
     *                              'input' => 'value',
     *                          ],
     *                          'fields' => true,
     *                      ],
     *                  ],
     *              ],
     *          ],
     *      ],
     * ],
     *
     * Warning: this method it is a naive implementation which does not take into account
     * conditional typed fragments. So use it with care for fields of interface and union types.
     *
     * @param  int  $depth  How many levels to include in output
     * @return array
     * @see \GraphQL\Type\Definition\ResolveInfo::getFieldSelection
     */
    public function getFieldsAndArgumentsSelection(int $depth = 0): array
    {
        $fields = [];

        foreach ($this->info->fieldNodes as $fieldNode) {
            if (! $fieldNode->selectionSet) {
                continue;
            }

            $fields = array_merge_recursive($fields, $this->foldSelectionSet($fieldNode->selectionSet, $depth));
        }

        return $fields;
    }

    /**
     * @param  SelectionSetNode  $selectionSet
     * @param  int  $descend
     * @return array
     * @see \GraphQL\Type\Definition\ResolveInfo::foldSelectionSet
     */
    private function foldSelectionSet(SelectionSetNode $selectionSet, int $descend): array
    {
        $fields = [];

        foreach ($selectionSet->selections as $selectionNode) {
            if ($selectionNode instanceof FieldNode) {
                $name = $selectionNode->name->value;

                $fields[$name] = [
                    'args' => [],
                    'fields' => $descend > 0 && ! empty($selectionNode->selectionSet)
                        ? $this->foldSelectionSet($selectionNode->selectionSet, $descend - 1)
                        : true,
                ];

                foreach ($selectionNode->arguments ?? [] as $argumentNode) {
                    $fields[$name]['args'][$argumentNode->name->value] = $this->getValue($argumentNode->value);
                }
            } elseif ($selectionNode instanceof FragmentSpreadNode) {
                $spreadName = $selectionNode->name->value;
                if (isset($this->info->fragments[$spreadName])) {
                    $fragment = $this->info->fragments[$spreadName];
                    $fields = (array) array_replace_recursive(
                        $this->foldSelectionSet($fragment->selectionSet, $descend),
                        $fields
                    );
                }
            } elseif ($selectionNode instanceof InlineFragmentNode) {
                $fields = (array) array_replace_recursive(
                    $this->foldSelectionSet($selectionNode->selectionSet, $descend),
                    $fields
                );
            }
        }

        return $fields;
    }

    private function getValue(ValueNode $value)
    {
        if ($value instanceof VariableNode) {
            $variableName = $value->name->value;

            return $this->info->variableValues[$variableName] ?? null;
        }

        if ($value instanceof IntValueNode) {
            return (int) $value->value;
        }

        if ($value instanceof FloatValueNode) {
            return (float) $value->value;
        }

        if ($value instanceof StringValueNode) {
            return (string) $value->value;
        }

        if ($value instanceof BooleanValueNode) {
            return (bool) $value->value;
        }

        if ($value instanceof EnumValueNode) {
            return (string) $value->value;
        }

        if ($value instanceof NullValueNode) {
            return;
        }

        if ($value instanceof ObjectValueNode) {
            return $this->getInputObjectValue($value);
        }

        if ($value instanceof ListValueNode) {
            return $this->getInputListObjectValue($value);
        }

        throw new RuntimeException('Failed to resolve unknown ValueNode type');
    }

    private function getInputObjectValue(ObjectValueNode $objectValueNode): array
    {
        $value = [];
        foreach ($objectValueNode->fields->getIterator() as $item) {
            if ($item instanceof ObjectFieldNode) {
                $value[$item->name->value] = $this->getValue($item->value);
            }
        }

        return $value;
    }

    private function getInputListObjectValue(ListValueNode $listValueNode): array
    {
        $value = [];
        foreach ($listValueNode->values as $valueNode) {
            $value[] = $this->getValue($valueNode);
        }

        return $value;
    }
}
