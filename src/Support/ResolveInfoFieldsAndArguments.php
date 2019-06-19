<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;

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
                    $fields[$name]['args'][$argumentNode->name->value] = $this->getValue($argumentNode);
                }
            } elseif ($selectionNode instanceof FragmentSpreadNode) {
                $spreadName = $selectionNode->name->value;
                if (isset($this->info->fragments[$spreadName])) {
                    $fragment = $this->info->fragments[$spreadName];
                    $fields = array_merge_recursive($this->foldSelectionSet($fragment->selectionSet, $descend),
                        $fields);
                }
            } elseif ($selectionNode instanceof InlineFragmentNode) {
                $fields = array_merge_recursive($this->foldSelectionSet($selectionNode->selectionSet, $descend),
                    $fields);
            }
        }

        return $fields;
    }

    private function getValue(ArgumentNode $argumentNode)
    {
        $value = $argumentNode->value;
        if ($value instanceof VariableNode) {
            $variableName = $value->name->value;

            return $this->info->variableValues[$variableName] ?? null;
        }

        return $argumentNode->value->value;
    }
}
