<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type as GraphqlType;

class Rules
{
    /**
     * @var  array<string,mixed>
     */
    private $queryArguments;
    /**
     * @var array<string,mixed>
     */
    private $requestArguments;

    /**
     * @param array<string,mixed> $queryArguments
     * @param array<string,mixed> $requestArguments
     */
    public function __construct(array $queryArguments, array $requestArguments)
    {
        $this->queryArguments = $queryArguments;
        $this->requestArguments = $requestArguments;
    }

    /**
     * @return array<string,mixed>
     */
    public function get(): array
    {
        return $this->getRules($this->queryArguments, null, $this->requestArguments);
    }

    /**
     * @param  array<string,mixed>|string|callable  $rules
     * @param  array<string,mixed>  $arguments
     * @return array<string,mixed>|string
     */
    protected function resolveRules($rules, array $arguments)
    {
        if (is_callable($rules)) {
            return call_user_func($rules, $arguments, $this->requestArguments);
        }

        return $rules;
    }

    /**
     * @param GraphqlType $type
     * @param string $prefix
     * @param array<string,mixed> $resolutionArguments
     * @return array<string,mixed>
     */
    protected function inferRulesFromType(GraphqlType $type, string $prefix, array $resolutionArguments): array
    {
        $isList = false;
        $rules = [];

        // make sure we are dealing with the actual type
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        // if it is an array type, add an array validation component
        if ($type instanceof ListOfType) {
            $type = $type->getWrappedType();

            $isList = true;
        }

        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        // if it is an input object type - the only type we care about here...
        if ($type instanceof InputObjectType) {
            // merge in the input type's rules

            if ($isList) {
                if (empty($resolutionArguments)) {
                    return [];
                }

                foreach ($resolutionArguments as $index => $input) {
                    $key = "{$prefix}.{$index}";
                    if ($input !== null) {
                        $rules = $rules + $this->getInputTypeRules($type, $key, $input);
                    }
                }

                return $rules;
            }

            $rules = $rules + $this->getInputTypeRules($type, $prefix, $resolutionArguments);
        }

        return $rules;
    }

    /**
     * @param InputObjectType $input
     * @param string $prefix
     * @param array<string,mixed> $resolutionArguments
     * @return array<string,mixed>
     */
    protected function getInputTypeRules(InputObjectType $input, string $prefix, array $resolutionArguments): array
    {
        return $this->getRules($input->getFields(), $prefix, $resolutionArguments);
    }

    /**
     * Get rules from fields.
     *
     * @param array<string,mixed> $fields
     * @param string|null $prefix
     * @param array<string,mixed> $resolutionArguments
     * @return array<string,mixed>
     */
    protected function getRules(array $fields, ?string $prefix, array $resolutionArguments): array
    {
        $rules = [];

        foreach ($fields as $name => $field) {
            $field = $field instanceof InputObjectField ? $field : (object) $field;

            $key = $prefix === null ? $name : "{$prefix}.{$name}";

            // get any explicitly set rules
            if (isset($field->rules)) {
                $rules[$key] = $this->resolveRules($field->rules, $resolutionArguments);
            }

            if (property_exists($field, 'type') && array_key_exists($name, $resolutionArguments) && is_array($resolutionArguments[$name])) {
                $rules = $rules + $this->inferRulesFromType($field->type, $key, $resolutionArguments[$name]);
            }
        }

        return $rules;
    }
}
