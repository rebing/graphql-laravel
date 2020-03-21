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
    private $parentType;
    /**
     * @var ResolveInfo
     */
    private $resolveInfo;

    /**
     * @var array<mixed,mixed>
     */
    private $fieldsAndArguments;

    /**
     * @param Type $parentType
     * @param ResolveInfo $resolveInfo
     */
    public function __construct(Type $parentType, ResolveInfo $resolveInfo)
    {
        $this->parentType = $parentType;
        $this->resolveInfo = $resolveInfo;
        $this->fieldsAndArguments = (new ResolveInfoFieldsAndArguments($this->resolveInfo))->getFieldsAndArgumentsSelection(5);
    }

    /**
     * @return array<array>
     */
    public function get(): array
    {
        if ($this->parentType instanceof WrappingType) {
            $this->parentType = $this->parentType->getWrappedType(true);
        }

        return [$this->fieldsAndArguments, $this->getRules($this->fieldsAndArguments, null, $this->parentType)];
    }

    /**
     * @param  array<string,mixed>|string|callable  $rules
     * @param  array<string,mixed>  $arguments
     * @return array<string,mixed>|string
     */
    protected function resolveRules($rules, array $arguments)
    {
        if (is_callable($rules)) {
            return call_user_func($rules, $arguments, $this->fieldsAndArguments);
        }

        return $rules;
    }

    /**
     * Get rules from fields.
     *
     * @param array<string,mixed> $fields
     * @param string|null $prefix
     * @return array<string,mixed>
     */
    protected function getRules(array $fields, ?string $prefix, Type $parentType): array
    {
        $rules = [];

        foreach ($fields as $name => $field) {
            $key = $prefix === null ? $name : "{$prefix}.{$name}";

            //If field doesn't exist on definition we don't select it
            try {
                if (method_exists($parentType, 'getField')) {
                    $fieldObject = $parentType->getField($name);
                } else {
                    continue;
                }
            } catch (InvariantViolation $e) {
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
