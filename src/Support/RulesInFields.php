<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class RulesInFields
{
    /**
     * @var  Type
     */
    protected $parentType;

    /**
     * @var array<mixed,mixed>
     */
    protected $fieldsAndArguments;

    /**
     * @param Type $parentType
     * @param array<string,mixed> $fieldsAndArgumentsSelection
     */
    public function __construct(Type $parentType, array $fieldsAndArgumentsSelection)
    {
        $this->parentType = $parentType instanceof WrappingType
            ? $parentType->getWrappedType(true)
            : $parentType;
        $this->fieldsAndArguments = $fieldsAndArgumentsSelection;
    }

    /**
     * @return array<array>
     */
    public function get(): array
    {
        return $this->getRules($this->fieldsAndArguments, null, $this->parentType);
    }

    /**
     * @param  array<string,mixed>|string|callable  $rules
     * @param  array<string,mixed>  $arguments
     * @return array<string,mixed>|string
     */
    protected function resolveRules($rules, array $arguments)
    {
        if (is_callable($rules)) {
            return call_user_func($rules, $arguments);
        }

        return $rules;
    }

    /**
     * Get rules from fields.
     *
     * @param array<string,mixed> $fields
     * @param string|null $prefix
     * @param Type $parentType
     * @return array<string,mixed>
     */
    protected function getRules(array $fields, ?string $prefix, Type $parentType): array
    {
        $rules = [];

        foreach ($fields as $name => $field) {
            $key = $prefix === null ? $name : "{$prefix}.{$name}";

            //If field doesn't exist on definition we don't select it
            if (method_exists($parentType, 'getField')) {
                $fieldObject = $parentType->getField($name);
            } else {
                continue;
            }

            if (is_array($field['fields'])) {
                $rules = $rules + $this->getRules($field['fields'], $key.'.fields', $fieldObject->getType());
            }

            $args = $fieldObject->config['args'] ?? [];

            foreach ($args as $argName => $info) {
                if (isset($info['rules'])) {
                    $rules[$key.'.args.'.$argName] = $this->resolveRules($info['rules'], $field['args']);
                }
            }
        }

        return $rules;
    }
}
