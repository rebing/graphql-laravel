<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\SchemaCache;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use Illuminate\Support\Facades\Config;
use Rebing\GraphQL\Support\AbstractPaginationType;
use Rebing\GraphQL\Support\PaginationType;
use Rebing\GraphQL\Support\SimplePaginationType;

class TypeConfigDecorator
{
    /** @var array<string, array<class-string>> */
    private array $classMapping;

    /**
     * @param array<string, mixed> $classMapping
     */
    public function __construct(array $classMapping)
    {
        $this->classMapping = $classMapping;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $config): array
    {
        if ($config['astNode'] instanceof ObjectTypeDefinitionNode) {
            if (\in_array($config['name'], ['Query', 'Mutation', 'Subscription'], true)) {
                $config = $this->decorateOperation($config);
            } else {
                $config = $this->decorateType($config);
            }
        } elseif ($config['astNode'] instanceof ScalarTypeDefinitionNode) {
            $config = $this->decorateScalar($config);
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function decorateOperation(array $config): array
    {
        $config['fields'] = function () use ($config): array {
            $name = strtolower($config['name']);
            $fields = $config['fields']();

            foreach ($fields as &$field) {
                $className = $this->classMapping[$name][$field['astNode']->name->value];

                $field['resolve'] = static function ($root, ...$arguments) use ($className) {
                    /** @var \Rebing\GraphQL\Support\Field $instance */
                    $instance = app($className);

                    $resolver = $instance->getResolver();

                    return $resolver ? $resolver($root, ...$arguments) : null;
                };
            }

            return $fields;
        };

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function decorateType(array $config): array
    {
        $className = $this->classMapping['types'][$config['astNode']->name->value] ?? null;

        if (null === $className) {
            // Detect pagination types
            if (str_ends_with($config['name'], 'Pagination')) {
                return $this->decoratePaginationType($config);
            }

            return $config;
        }

        $config['model'] = function () use ($className): ?string {
            /** @var \Rebing\GraphQL\Support\Type $type */
            $type = app($className);

            $attributes = $type->getAttributes();

            return $attributes['model'] ?? null;
        };

        $config['fields'] = function () use ($config, $className): array {
            /** @var \Rebing\GraphQL\Support\Type $type */
            $type = app($className);

            return $this->decorateFields($config, $type->getFields());
        };

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function decoratePaginationType(array $config): array
    {
        if (str_ends_with($config['name'], 'SimplePagination')) {
            $className = Config::get('graphql.simple_pagination_type', SimplePaginationType::class);
            $typeName = substr($config['name'], 0, -\strlen('SimplePagination'));
        } elseif (str_ends_with($config['name'], 'Pagination')) {
            $className = Config::get('graphql.pagination_type', PaginationType::class);
            $typeName = substr($config['name'], 0, -\strlen('Pagination'));
        } else {
            return $config;
        }

        $config['fields'] = function () use ($config, $typeName, $className): array {
            if (is_subclass_of($className, AbstractPaginationType::class)) {
                $type = new $className($typeName);

                return $this->decorateFields($config, $type->getPaginationFields());
            }

            return $config['fields']();
        };

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function decorateScalar(array $config): array
    {
        $className = $this->classMapping['types'][$config['astNode']->name->value];

        /** @var \GraphQL\Type\Definition\ScalarType $scalar */
        $scalar = app($className);

        $config['serialize'] = [$scalar, 'serialize'];
        $config['parseValue'] = [$scalar, 'parseValue'];
        $config['parseLiteral'] = [$scalar, 'parseLiteral'];

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $fieldDefinitions
     *
     * @return array<string, mixed>
     */
    protected function decorateFields(array $config, array $fieldDefinitions): array
    {
        $fields = $config['fields']();

        $props = [
            'alias',
            'always',
            'is_relation',
            'privacy',
            'resolve',
            'selectable',
        ];

        foreach ($fields as &$field) {
            $fieldName = $field['astNode']->name->value;

            foreach ($props as $prop) {
                if (isset($fieldDefinitions[$fieldName][$prop])) {
                    $field[$prop] = $fieldDefinitions[$fieldName][$prop];
                }
            }
        }

        return $fields;
    }
}
