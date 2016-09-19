<?php

namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;

class SelectFields {

    /* @var $select array */
    private $select = [];
    /* @var $relations array */
    private $relations = [];

    public function __construct(ResolveInfo $info)
    {
        $parentType = $info->parentType->config['fields'][$info->fieldName]['type'];
        $fields = self::getSelectableFieldsAndRelations($info->getFieldSelection(5), $parentType);

        $this->select = $fields[0];
        $this->relations = $fields[1];
    }

    /**
     * Retrieve the fields (top level) and relations that
     * will be selected with the query
     *
     * @return array | Closure - if first recursion, return an array,
     *      where the first key is 'select' array and second is 'with' array.
     *      On other recursions return a closure that will be used in with
     */
    public static function getSelectableFieldsAndRelations(array $requestedFields, $parentType, $topLevel = true)
    {
        $select = [];
        $with = [];

        if(is_a($parentType, ListOfType::class))
        {
            $parentType = $parentType->getWrappedType();
        }
        $primaryKey = app($parentType->config['model'])->getKeyName();

        foreach($requestedFields as $key => $field)
        {
            // With
            if(is_array($field))
            {
                // Get the primary key, so that 'with' queries could be made
                $parentType = GraphQL::type($parentType->config['relations'][$key]);

                $with[$key] = self::getSelectableFieldsAndRelations($field, $parentType, false);
            }
            // Select
            else
            {
                $select[] = $key;
            }
        }

        // If a primary key is given, but not in the selects, add it
        if( ! is_null($primaryKey) && ! in_array($primaryKey, $select))
        {
            $select[] = $primaryKey;
        }

        if($topLevel)
        {
            return [$select, $with];
        }
        else
        {
            return function($query) use ($with, $select)
            {
                $query->select($select);
                $query->with($with);
            };
        }
    }

    public function getSelect()
    {
        return $this->select;
    }

    public function getRelations()
    {
        return $this->relations;
    }

}