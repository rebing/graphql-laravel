<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphqlType;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

/**
 * @property string $name
 */
abstract class Type implements TypeConvertible
{
    protected $attributes = [];
    /**
     * Set to `true` in your type when it should reflect an InputObject.
     * @var bool
     * @deprecated Use InputType instead
     * @see InputType
     */
    protected $inputObject = false;
    /**
     * Set to `true` in your type when it should reflect an Enum.
     * @var bool
     * @deprecated Use EnumType instead
     * @see EnumType
     */
    protected $enumObject = false;

    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<int|string,array|string|FieldDefinition|Field>
     */
    public function fields(): array
    {
        return [];
    }

    public function interfaces(): array
    {
        return [];
    }

    protected function getFieldResolver(string $name, array $field): ?callable
    {
        if (isset($field['resolve'])) {
            return $field['resolve'];
        }

        $resolveMethod = 'resolve'.Str::studly($name).'Field';

        if (method_exists($this, $resolveMethod)) {
            $resolver = [$this, $resolveMethod];

            return function () use ($resolver) {
                $args = func_get_args();

                return call_user_func_array($resolver, $args);
            };
        }

        if (isset($field['alias']) && is_string($field['alias'])) {
            $alias = $field['alias'];

            return function ($type) use ($alias) {
                return $type->{$alias};
            };
        }

        return null;
    }

    public function getFields(): array
    {
        $fields = $this->fields();
        $allFields = [];
        foreach ($fields as $name => $field) {
            if (is_string($field)) {
                $field = app($field);
                $field->name = $name;
                $allFields[$name] = $field->toArray();
            } elseif ($field instanceof Field) {
                $field->name = $name;
                $allFields[$name] = $field->toArray();
            } elseif ($field instanceof FieldDefinition) {
                $allFields[$field->name] = $field;
            } else {
                $resolver = $this->getFieldResolver($name, $field);
                if ($resolver) {
                    $field['resolve'] = $resolver;
                }
                $allFields[$name] = $field;
            }
        }

        return $allFields;
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes();
        $interfaces = $this->interfaces();

        $attributes = array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            },
        ], $attributes);

        if (count($interfaces)) {
            $attributes['interfaces'] = $interfaces;
        }

        return $attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function toType(): GraphqlType
    {
        if ($this->inputObject) {
            return new InputObjectType($this->toArray());
        }
        if ($this->enumObject) {
            return new EnumType($this->toArray());
        }

        return new ObjectType($this->toArray());
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return $attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }
}
