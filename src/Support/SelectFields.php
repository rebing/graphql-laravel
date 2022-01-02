<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type as GraphqlType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class SelectFields
{
    /** @var array */
    protected $select = [];
    /** @var array */
    protected $relations = [];

    /** @var GraphqlType */
    protected $parentType;

    /** @var array */
    protected $queryArgs;

    /** @var mixed */
    protected $ctx;

    /** @var array<string,mixed> */
    protected $fieldsAndArguments;

    public const ALWAYS_RELATION_KEY = 'ALWAYS_RELATION_KEY';

    /**
     * @param array $queryArgs Arguments given with the query/mutation
     * @param mixed $ctx The GraphQL context; can be anything and is only passed through
     *                   Can be created/overridden by \Rebing\GraphQL\GraphQLController::queryContext
     * @param array<string,mixed> $fieldsAndArguments Field and argument tree
     */
    public function __construct(GraphqlType $parentType, array $queryArgs, $ctx, array $fieldsAndArguments)
    {
        if ($parentType instanceof WrappingType) {
            $parentType = $parentType->getWrappedType(true);
        }

        $this->parentType = $parentType;
        $this->queryArgs = $queryArgs;
        $this->ctx = $ctx;
        $this->fieldsAndArguments = $fieldsAndArguments;

        $requestedFields = [
            'args' => $queryArgs,
            'fields' => $fieldsAndArguments,
        ];

        /** @var array{0:mixed[],1:mixed[]} $result */
        $result = $this->getSelectableFieldsAndRelations($requestedFields, $parentType);

        [$this->select, $this->relations] = $result;
    }

    /**
     * Retrieve the fields (top level) and relations that
     * will be selected with the query.
     *
     * @return array|Closure - if first recursion, return an array,
     *                       where the first key is 'select' array and second is 'with' array.
     *                       On other recursions return a closure that will be used in with
     */
    public function getSelectableFieldsAndRelations(
        array $requestedFields,
        GraphqlType $parentType,
        ?Closure $customQuery = null,
        bool $topLevel = true
    ) {
        $select = [];
        $with = [];

        if ($parentType instanceof WrappingType) {
            $parentType = $parentType->getWrappedType(true);
        }
        $parentTable = $this->getTableNameFromParentType($parentType);
        $primaryKey = $this->getPrimaryKeyFromParentType($parentType);

        $this->handleFields($requestedFields, $parentType, $select, $with);

        // If a primary key is given, but not in the selects, add it
        if (null !== $primaryKey) {
            $primaryKey = $parentTable ? ($parentTable . '.' . $primaryKey) : $primaryKey;

            if (!\in_array($primaryKey, $select)) {
                $select[] = $primaryKey;
            }
        }

        if ($topLevel) {
            return [$select, $with];
        }

        return function ($query) use ($with, $select, $customQuery, $requestedFields): void {
            if ($customQuery) {
                $query = $customQuery($requestedFields['args'], $query, $this->getCtx()) ?? $query;
            }

            $query->select($select);
            $query->with($with);
        };
    }

    protected function getTableNameFromParentType(GraphqlType $parentType): ?string
    {
        return isset($parentType->config['model']) ? app($parentType->config['model'])->getTable() : null;
    }

    protected function getPrimaryKeyFromParentType(GraphqlType $parentType): ?string
    {
        return isset($parentType->config['model']) ? app($parentType->config['model'])->getKeyName() : null;
    }

    /**
     * Get the selects and withs from the given fields
     * and recurse if necessary.
     *
     * @param array<string,mixed> $requestedFields
     * @param array $select Passed by reference, adds further fields to select
     * @param array $with Passed by reference, adds further relations
     */
    protected function handleFields(
        array $requestedFields,
        GraphqlType $parentType,
        array &$select,
        array &$with
    ): void {
        $parentTable = $this->isMongodbInstance($parentType) ? null : $this->getTableNameFromParentType($parentType);

        foreach ($requestedFields['fields'] as $key => $field) {
            // Ignore __typename, as it's a special case
            if ('__typename' === $key) {
                continue;
            }

            // Always select foreign key
            if ($field === static::ALWAYS_RELATION_KEY) {
                $this->addFieldToSelect($key, $select, $parentTable, false);

                continue;
            }

            // If field doesn't exist on definition we don't select it
            try {
                if (method_exists($parentType, 'getField')) {
                    $fieldObject = $parentType->getField($key);
                } else {
                    continue;
                }
            } catch (InvariantViolation $e) {
                continue;
            }

            $parentTypeUnwrapped = $parentType;

            if ($parentTypeUnwrapped instanceof WrappingType) {
                $parentTypeUnwrapped = $parentTypeUnwrapped->getWrappedType(true);
            }

            // First check if the field is even accessible
            $canSelect = $this->validateField($fieldObject);

            if (true === $canSelect) {
                // Add a query, if it exists
                $customQuery = $fieldObject->config['query'] ?? null;

                // Check if the field is a relation that needs to be requested from the DB
                $queryable = $this->isQueryable($fieldObject->config);

                // Pagination
                if (is_a($parentType, Config::get('graphql.pagination_type', PaginationType::class)) ||
                    is_a($parentType, Config::get('graphql.simple_pagination_type', SimplePaginationType::class))) {
                    /* @var GraphqlType $fieldType */
                    $fieldType = $fieldObject->config['type'];
                    $this->handleFields(
                        $field,
                        $fieldType->getWrappedType(),
                        $select,
                        $with
                    );
                }
                // With

                elseif (\is_array($field['fields']) && !empty($field['fields']) && $queryable) {
                    if (isset($parentType->config['model'])) {
                        // Get the next parent type, so that 'with' queries could be made
                        // Both keys for the relation are required (e.g 'id' <-> 'user_id')
                        $relationsKey = $fieldObject->config['alias'] ?? $key;
                        $relation = \call_user_func([app($parentType->config['model']), $relationsKey]);

                        $this->handleRelation($select, $relation, $parentTable, $field);

                        // New parent type, which is the relation
                        $newParentType = $parentType->getField($key)->config['type'];

                        $this->addAlwaysFields($fieldObject, $field, $parentTable, true);

                        $with[$relationsKey] = $this->getSelectableFieldsAndRelations(
                            $field,
                            $newParentType,
                            $customQuery,
                            false
                        );
                    } elseif (is_a($parentTypeUnwrapped, \GraphQL\Type\Definition\InterfaceType::class)) {
                        $this->handleInterfaceFields(
                            $field,
                            $parentTypeUnwrapped,
                            $select,
                            $with,
                            $fieldObject,
                            $key,
                            $customQuery
                        );
                    } else {
                        $this->handleFields($field, $fieldObject->config['type'], $select, $with);
                    }
                }
                // Select
                else {
                    $key = $fieldObject->config['alias']
                        ?? $key;
                    $key = $key instanceof Closure ? $key() : $key;

                    $this->addFieldToSelect($key, $select, $parentTable, false);

                    $this->addAlwaysFields($fieldObject, $select, $parentTable);
                }
            }
            // If privacy does not allow the field, return it as null
            elseif (null === $canSelect) {
                $fieldObject->resolveFn = function (): void {
                };
            }
            // If allowed field, but not selectable
            elseif (false === $canSelect) {
                $this->addAlwaysFields($fieldObject, $select, $parentTable);
            }
        }

        // If parent type is an union or interface we select all fields
        // because we don't know which other fields are required
        if (is_a($parentType, UnionType::class) || is_a($parentType, \GraphQL\Type\Definition\InterfaceType::class)) {
            $select = ['*'];
        }
    }

    protected function isMongodbInstance(GraphqlType $parentType): bool
    {
        $mongoType = 'Jenssegers\Mongodb\Eloquent\Model';

        return isset($parentType->config['model']) ? app($parentType->config['model']) instanceof $mongoType : false;
    }

    /**
     * @param string|Expression $field
     * @param array $select Passed by reference, adds further fields to select
     */
    protected function addFieldToSelect($field, array &$select, ?string $parentTable, bool $forRelation): void
    {
        if ($field instanceof Expression) {
            $select[] = $field;

            return;
        }

        if ($forRelation && !\array_key_exists($field, $select['fields'])) {
            $select['fields'][$field] = [
                'args' => [],
                'fields' => true,
            ];
        } elseif (!$forRelation && !\in_array($field, $select)) {
            $field = $parentTable ? ($parentTable . '.' . $field) : $field;

            if (!\in_array($field, $select)) {
                $select[] = $field;
            }
        }
    }

    /**
     * Check the privacy status, if it's given.
     *
     * @param FieldDefinition $fieldObject Validated field
     *
     * @return bool|null `true`  if selectable
     *                   `false` if not selectable, but allowed
     *                   `null`  if not allowed
     */
    protected function validateField(FieldDefinition $fieldObject): ?bool
    {
        $selectable = true;

        // If not a selectable field
        if (isset($fieldObject->config['selectable']) && false === $fieldObject->config['selectable']) {
            $selectable = false;
        }

        if (isset($fieldObject->config['privacy'])) {
            $privacyClass = $fieldObject->config['privacy'];

            switch ($privacyClass) {
                // If privacy given as a closure
                case \is_callable($privacyClass):
                    if (false === $privacyClass($this->getQueryArgs(), $this->getCtx())) {
                        $selectable = null;
                    }

                    break;
                // If Privacy class given
                case \is_string($privacyClass):
                    /** @var Privacy $instance */
                    $instance = app($privacyClass);

                    if (false === $instance->fire($this->getQueryArgs(), $this->getCtx())) {
                        $selectable = null;
                    }

                    break;

                default:
                    throw new RuntimeException(
                        \Safe\sprintf(
                            "Unsupported use of 'privacy' configuration on field '%s'.",
                            $fieldObject->name
                        )
                    );
            }
        }

        return $selectable;
    }

    /**
     * Determines whether the fieldObject is queryable.
     */
    protected function isQueryable(array $fieldObject): bool
    {
        return ($fieldObject['is_relation'] ?? true) === true;
    }

    /**
     * @param Relation $relation
     * @param array $field
     */
    protected function handleRelation(array &$select, $relation, ?string $parentTable, &$field): void
    {
        // Add the foreign key here, if it's a 'belongsTo'/'belongsToMany' relation
        if (method_exists($relation, 'getForeignKey')) {
            $foreignKey = $relation->getForeignKey();
        } elseif (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
            $foreignKey = $relation->getQualifiedForeignPivotKeyName();
        } else {
            /** @var BelongsTo|HasManyThrough|HasOneOrMany $relation */
            $foreignKey = $relation->getQualifiedForeignKeyName();
        }
        $foreignKey = $parentTable ? ($parentTable . '.' . \Safe\preg_replace(
            '/^' . preg_quote($parentTable, '/') . '\./',
            '',
            $foreignKey
        )) : $foreignKey;

        if (is_a($relation, MorphTo::class)) {
            $foreignKeyType = $relation->getMorphType();
            $foreignKeyType = $parentTable ? ($parentTable . '.' . $foreignKeyType) : $foreignKeyType;

            if (!\in_array($foreignKey, $select)) {
                $select[] = $foreignKey;
            }

            if (!\in_array($foreignKeyType, $select)) {
                $select[] = $foreignKeyType;
            }
        } elseif (is_a($relation, BelongsTo::class)) {
            if (!\in_array($foreignKey, $select)) {
                $select[] = $foreignKey;
            }
        } // If 'HasMany', then add it in the 'with'
        elseif ((is_a($relation, HasMany::class) || is_a($relation, MorphMany::class) || is_a(
            $relation,
            HasOne::class
        ) || is_a($relation, MorphOne::class)) &&
            !\array_key_exists($foreignKey, $field)) {
            $segments = explode('.', $foreignKey);
            $foreignKey = end($segments);

            if (!\array_key_exists($foreignKey, $field)) {
                $field['fields'][$foreignKey] = static::ALWAYS_RELATION_KEY;
            }

            if (is_a($relation, MorphMany::class) || is_a($relation, MorphOne::class)) {
                $field['fields'][$relation->getMorphType()] = static::ALWAYS_RELATION_KEY;
            }
        }
    }

    /**
     * Add selects that are given by the 'always' attribute.
     *
     * @param array $select Passed by reference, adds further fields to select
     */
    protected function addAlwaysFields(
        FieldDefinition $fieldObject,
        array &$select,
        ?string $parentTable,
        bool $forRelation = false
    ): void {
        if (isset($fieldObject->config['always'])) {
            $always = $fieldObject->config['always'];

            if (\is_string($always)) {
                $always = explode(',', $always);
            }

            // Get as 'field' => true
            foreach ($always as $field) {
                $this->addFieldToSelect($field, $select, $parentTable, $forRelation);
            }
        }
    }

    protected function handleInterfaceFields(
        array $field,
        GraphqlType $parentType,
        array &$select,
        array &$with,
        FieldDefinition $fieldObject,
        string $key,
        ?Closure $customQuery
    ): void {
        $relationsKey = Arr::get($fieldObject->config, 'alias', $key);

        $with[$relationsKey] = function ($query) use (
            $field,
            $parentType,
            &$select,
            $customQuery,
            $key,
            $fieldObject
        ) {
            $parentTable = $this->isMongodbInstance($parentType) ? null : $this->getTableNameFromParentType($parentType);

            $this->handleRelation($select, $query, $parentTable, $field);

            // New parent type, which is the relation
            try {
                if (method_exists($parentType, 'getField')) {
                    $newParentType = $parentType->getField($key)->config['type'];
                    $customQuery = $parentType->getField($key)->config['query'] ?? $customQuery;
                } else {
                    return $query;
                }
            } catch (InvariantViolation $e) {
                return $query;
            }

            $this->addAlwaysFields($fieldObject, $field, $parentTable, true);

            // Find the type of the current relation by comparing table names
            if (isset($parentType->config['types'])) {
                $typesFiltered = array_filter(
                    $parentType->config['types'](),
                    function (GraphqlType $type) use ($query) {
                        /* @var Relation $query */
                        return app($type->config['model'])->getTable() === $query->getParent()->getTable();
                    }
                );
                $typesFiltered = array_values($typesFiltered ?? []);

                if (1 === \count($typesFiltered)) {
                    /* @var GraphqlType $type */
                    $type = $typesFiltered[0];
                    $relationField = $type->getField($key);
                    $newParentType = $relationField->config['type'];
                    // If a custom query is available on the selected type, it should replace the interface's one
                    $customQuery = $relationField->config['query'] ?? $customQuery;
                }
            }

            if ($newParentType instanceof WrappingType) {
                $newParentType = $newParentType->getWrappedType(true);
            }

            /** @var callable $callable */
            $callable = $this->getSelectableFieldsAndRelations(
                $field,
                $newParentType,
                $customQuery,
                false
            );

            return $callable($query);
        };
    }

    public function getParentType(): GraphqlType
    {
        return $this->parentType;
    }

    public function getQueryArgs(): array
    {
        return $this->queryArgs;
    }

    public function getCtx()
    {
        return $this->ctx;
    }

    /**
     * @return array<string,mixed>
     */
    public function getFieldsAndArguments(): array
    {
        return $this->fieldsAndArguments;
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }
}
