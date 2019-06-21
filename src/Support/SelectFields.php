<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use Illuminate\Database\Query\Expression;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type as GraphqlType;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SelectFields
{
    /** @var array */
    private $select = [];
    /** @var array */
    private $relations = [];
    /** @var array */
    private static $privacyValidations = [];

    const FOREIGN_KEY = 'foreignKey';

    /**
     * @param  ResolveInfo  $info
     * @param  GraphqlType  $parentType
     * @param  array  $args  - arguments given with the query
     */
    public function __construct(ResolveInfo $info, GraphqlType $parentType, array $args)
    {
        if ($parentType instanceof WrappingType) {
            $parentType = $parentType->getWrappedType(true);
        }

        $requestedFields = $this->getFieldSelection($info, $args, 5);
        $fields = self::getSelectableFieldsAndRelations($requestedFields, $parentType);
        $this->select = $fields[0];
        $this->relations = $fields[1];
    }

    private function getFieldSelection(ResolveInfo $resolveInfo, array $args, int $depth): array
    {
        $resolveInfoFieldsAndArguments = new ResolveInfoFieldsAndArguments($resolveInfo);

        return [
            'args' => $args,
            'fields' => $resolveInfoFieldsAndArguments->getFieldsAndArgumentsSelection($depth),
        ];
    }

    /**
     * Retrieve the fields (top level) and relations that
     * will be selected with the query.
     *
     * @param  array  $requestedFields
     * @param  GraphqlType  $parentType
     * @param  Closure|null  $customQuery
     * @param  bool  $topLevel
     * @return array|Closure - if first recursion, return an array,
     *               where the first key is 'select' array and second is 'with' array.
     *               On other recursions return a closure that will be used in with
     */
    public static function getSelectableFieldsAndRelations(array $requestedFields, GraphqlType $parentType, ?Closure $customQuery = null, bool $topLevel = true)
    {
        $select = [];
        $with = [];

        if ($parentType instanceof WrappingType) {
            $parentType = $parentType->getWrappedType(true);
        }
        $parentTable = self::getTableNameFromParentType($parentType);
        $primaryKey = self::getPrimaryKeyFromParentType($parentType);

        self::handleFields($requestedFields, $parentType, $select, $with);

        // If a primary key is given, but not in the selects, add it
        if (! is_null($primaryKey)) {
            if (is_array($primaryKey)) {
                foreach ($primaryKey as $key) {
                    $select[] = $parentTable ? ($parentTable.'.'.$key) : $key;
                }
            } else {
                $primaryKey = $parentTable ? ($parentTable.'.'.$primaryKey) : $primaryKey;
                if (! in_array($primaryKey, $select)) {
                    $select[] = $primaryKey;
                }
            }
        }

        if ($topLevel) {
            return [$select, $with];
        } else {
            return function ($query) use ($with, $select, $customQuery, $requestedFields) {
                if ($customQuery) {
                    $query = $customQuery($requestedFields['args'], $query);
                }

                $query->select($select);
                $query->with($with);
            };
        }
    }

    /**
     * Get the selects and withs from the given fields
     * and recurse if necessary.
     * @param  array<string,mixed>  $requestedFields
     * @param  GraphqlType  $parentType
     * @param  array  $select Passed by reference, adds further fields to select
     * @param  array  $with Passed by reference, adds further relations
     */
    protected static function handleFields(array $requestedFields, GraphqlType $parentType, array &$select, array &$with): void
    {
        $parentTable = self::isMongodbInstance($parentType) ? null : self::getTableNameFromParentType($parentType);

        foreach ($requestedFields['fields'] as $key => $field) {
            // Ignore __typename, as it's a special case
            if ($key === '__typename') {
                continue;
            }

            // Always select foreign key
            if ($field === self::FOREIGN_KEY) {
                self::addFieldToSelect($key, $select, $parentTable, false);
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

            // First check if the field is even accessible
            $canSelect = self::validateField($fieldObject, $field['args']);
            if ($canSelect === true) {
                // Add a query, if it exists
                $customQuery = Arr::get($fieldObject->config, 'query');

                // Check if the field is a relation that needs to be requested from the DB
                $queryable = self::isQueryable($fieldObject->config);

                // Pagination
                if (is_a($parentType, config('graphql.pagination_type', PaginationType::class))) {
                    self::handleFields($field, $fieldObject->config['type']->getWrappedType(), $select, $with);
                }
                // With
                elseif (is_array($field['fields']) && $queryable) {
                    if (isset($parentType->config['model'])) {
                        // Get the next parent type, so that 'with' queries could be made
                        // Both keys for the relation are required (e.g 'id' <-> 'user_id')
                        $relation = call_user_func([app($parentType->config['model']), $key]);
                        // Add the foreign key here, if it's a 'belongsTo'/'belongsToMany' relation
                        if (method_exists($relation, 'getForeignKey')) {
                            $foreignKey = $relation->getForeignKey();
                        } elseif (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
                            $foreignKey = $relation->getQualifiedForeignPivotKeyName();
                        } else {
                            $foreignKey = $relation->getQualifiedForeignKeyName();
                        }

                        $foreignKey = $parentTable ? ($parentTable.'.'.preg_replace('/^'.preg_quote($parentTable).'\./', '', $foreignKey)) : $foreignKey;

                        if (is_a($relation, MorphTo::class)) {
                            $foreignKeyType = $relation->getMorphType();
                            $foreignKeyType = $parentTable ? ($parentTable.'.'.$foreignKeyType) : $foreignKeyType;

                            if (! in_array($foreignKey, $select)) {
                                $select[] = $foreignKey;
                            }

                            if (! in_array($foreignKeyType, $select)) {
                                $select[] = $foreignKeyType;
                            }
                        } elseif (is_a($relation, BelongsTo::class)) {
                            if (! in_array($foreignKey, $select)) {
                                $select[] = $foreignKey;
                            }
                        }
                        // If 'HasMany', then add it in the 'with'
                        elseif ((is_a($relation, HasMany::class) || is_a($relation, MorphMany::class) || is_a($relation, HasOne::class) || is_a($relation, MorphOne::class))
                            && ! array_key_exists($foreignKey, $field)
                        ) {
                            $segments = explode('.', $foreignKey);
                            $foreignKey = end($segments);
                            if (! array_key_exists($foreignKey, $field)) {
                                $field['fields'][$foreignKey] = self::FOREIGN_KEY;
                            }
                        }

                        // New parent type, which is the relation
                        $newParentType = $parentType->getField($key)->config['type'];

                        self::addAlwaysFields($fieldObject, $field, $parentTable, true);

                        $with[$key] = self::getSelectableFieldsAndRelations($field, $newParentType, $customQuery, false);
                    } else {
                        self::handleFields($field, $fieldObject->config['type'], $select, $with);
                    }
                }
                // Select
                else {
                    $column = self::getColumn($key, $fieldObject);

                    self::addFieldToSelect($key, $select, $parentTable, false, $column);

                    self::addAlwaysFields($fieldObject, $select, $parentTable);
                }
            }
            // If privacy does not allow the field, return it as null
            elseif ($canSelect === null) {
                $fieldObject->resolveFn = function () {
                };
            }
            // If allowed field, but not selectable
            elseif ($canSelect === false) {
                self::addAlwaysFields($fieldObject, $select, $parentTable);
            }
        }

        // If parent type is an union we select all fields
        // because we don't know which other fields are required
        if (is_a($parentType, UnionType::class)) {
            $select = ['*'];
        }
    }

    /**
     * Check the privacy status, if it's given.
     *
     * @param  FieldDefinition  $fieldObject
     * @param  array  $fieldArgs Arguments given with the field
     * @return bool|null - true, if selectable; false, if not selectable, but allowed;
     *              null, if not allowed
     */
    protected static function validateField(FieldDefinition $fieldObject, array $fieldArgs): ?bool
    {
        $selectable = true;

        // If not a selectable field
        if (isset($fieldObject->config['selectable']) && $fieldObject->config['selectable'] === false) {
            $selectable = false;
        }

        if (isset($fieldObject->config['privacy'])) {
            $privacyClass = $fieldObject->config['privacy'];

            // If privacy given as a closure
            if (is_callable($privacyClass) && call_user_func($privacyClass, $fieldArgs) === false) {
                $selectable = null;
            }
            // If Privacy class given
            elseif (is_string($privacyClass)) {
                if (Arr::has(self::$privacyValidations, $privacyClass)) {
                    $validated = self::$privacyValidations[$privacyClass];
                } else {
                    $validated = call_user_func([app($privacyClass), 'fire'], $fieldArgs);
                    self::$privacyValidations[$privacyClass] = $validated;
                }

                if (! $validated) {
                    $selectable = null;
                }
            }
        }

        return $selectable;
    }

    /**
     * Determines whether the fieldObject is queryable.
     *
     * @param array $fieldObject
     *
     * @return bool
     */
    private static function isQueryable(array $fieldObject): bool
    {
        return Arr::get($fieldObject, 'is_relation', true) === true;
    }

    /**
     * Determines whether the fieldObject is queryable.
     *
     * @param $fieldObject
     *
     * @return Expression | null | string
     */
    private static function getColumn(string $key, $fieldObject)
    {
        if (isset($fieldObject->config['alias'])) {
            $alias = $fieldObject->config['alias'];

            if (is_string($alias)) {
                return $alias.' AS '.$key;
            } elseif (is_callable($alias)) {
                return DB::raw('('.$alias().') AS '.$key);
            }
        }
    }

    /**
     * Add selects that are given by the 'always' attribute.
     *
     * @param  FieldDefinition  $fieldObject
     * @param  array  $select Passed by reference, adds further fields to select
     * @param  string|null  $parentTable
     * @param  bool  $forRelation
     */
    protected static function addAlwaysFields(FieldDefinition $fieldObject, array &$select, ?string $parentTable, bool $forRelation = false): void
    {
        if (isset($fieldObject->config['always'])) {
            $always = $fieldObject->config['always'];

            if (is_string($always)) {
                $always = explode(',', $always);
            }

            // Get as 'field' => true
            foreach ($always as $field) {
                self::addFieldToSelect($field, $select, $parentTable, $forRelation);
            }
        }
    }

    /**
     * @param  string  $field
     * @param  array  $select Passed by reference, adds further fields to select
     * @param  string|null  $parentTable
     * @param  bool  $forRelation
     */
    protected static function addFieldToSelect(string $field, array &$select, ?string $parentTable, bool $forRelation, $column = null): void
    {
        if ($forRelation && ! array_key_exists($field, $select)) {
            $select[$field] = true;
        } elseif (! $forRelation && ! in_array($field, $select)) {
            $field = $parentTable ? ($parentTable.'.'.$field) : $field;
            $dbColumn = $column ?: $field;
            if (is_string($column)) {
                $dbColumn = $parentTable ? ($parentTable.'.'.$dbColumn) : $dbColumn;
            }

            if (! in_array($field, $select)) {
                $select[] = $dbColumn;
            }
        }
    }

    private static function getPrimaryKeyFromParentType(GraphqlType $parentType): ?string
    {
        return isset($parentType->config['model']) ? app($parentType->config['model'])->getKeyName() : null;
    }

    private static function getTableNameFromParentType(GraphqlType $parentType): ?string
    {
        return isset($parentType->config['model']) ? app($parentType->config['model'])->getTable() : null;
    }

    private static function isMongodbInstance(GraphqlType $parentType): bool
    {
        $mongoType = 'Jenssegers\Mongodb\Eloquent\Model';

        return isset($parentType->config['model']) ? app($parentType->config['model']) instanceof $mongoType : false;
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
