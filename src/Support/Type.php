<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;
use RuntimeException;

/**
 * @property string $name
 */
abstract class Type implements TypeConvertible
{
    /** @var array<string,mixed> */
    protected $attributes = [];

    /**
     * @return array<string,mixed>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<string,array<string,mixed>|string|FieldDefinition|Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * @return array<GraphqlType|callable>
     */
    public function interfaces(): array
    {
        return [];
    }

    protected function getFieldResolver(string $name, array $field): ?callable
    {
        if (isset($field['resolve'])) {
            return $field['resolve'];
        }

        $resolveMethod = 'resolve' . Str::studly($name) . 'Field';

        if (method_exists($this, $resolveMethod)) {
            $resolver = [$this, $resolveMethod];

            return function () use ($resolver) {
                $args = \func_get_args();

                return \call_user_func_array($resolver, $args);
            };
        }

        if (isset($field['alias']) && \is_string($field['alias']) && !($this instanceof InputType)) {
            $alias = $field['alias'];

            return function ($type) use ($alias) {
                return $type->{$alias};
            };
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    public function getFields(): array
    {
        $fields = $this->fields();
        $allFields = [];

        foreach ($fields as $name => $field) {
            if (\is_string($field)) {
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

                if (isset($field['privacy'])) {
                    $field['resolve'] = static::wrapResolverWithPrivacy(
                        $field['resolve'] ?? null,
                        $field['privacy'],
                    );
                }

                $allFields[$name] = $field;
            }
        }

        return $allFields;
    }

    /**
     * Wrap a field resolver with a privacy check.
     *
     * If privacy denies access, `null` is returned without calling the
     * original resolver.  When no original resolver is provided the
     * default field resolver from webonyx/graphql-php is used.
     *
     * @param mixed $privacy Closure or Privacy class name
     */
    protected static function wrapResolverWithPrivacy(?callable $originalResolve, $privacy): Closure
    {
        return function ($root, array $args, $context, ResolveInfo $info) use ($originalResolve, $privacy) {
            if (!static::evaluatePrivacy($privacy, $args, $context)) {
                return null;
            }

            if ($originalResolve) {
                return $originalResolve($root, $args, $context, $info);
            }

            return Executor::defaultFieldResolver($root, $args, $context, $info);
        };
    }

    /**
     * Evaluate a privacy configuration (closure or class name).
     *
     * @param mixed $privacy Closure or Privacy class name
     * @param array<string,mixed> $args The field's own arguments
     * @param mixed $context The query context value
     */
    protected static function evaluatePrivacy(mixed $privacy, array $args, $context): bool
    {
        if (\is_callable($privacy)) {
            return (bool) $privacy($args, $context);
        }

        if (\is_string($privacy)) {
            /** @var Privacy $instance */
            $instance = app($privacy);

            return $instance->fire($args, $context);
        }

        throw new RuntimeException(
            "Unsupported use of 'privacy' configuration: expected a callable or a class-string.",
        );
    }

    /**
     * Get the attributes from the container.
     * @return array<string,mixed>
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes();

        return array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            },
            'interfaces' => function () {
                return $this->interfaces();
            },
        ], $attributes);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function toType(): GraphqlType
    {
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

    /**
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }
}
