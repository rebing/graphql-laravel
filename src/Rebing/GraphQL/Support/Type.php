<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Illuminate\Support\Fluent;

class Type extends Fluent {
    
    protected static $instances = [];
    
    protected $inputObject = false;
    protected $enumObject = false;
    protected $unionType = false;

    public function attributes()
    {
        return [];
    }
    
    public function fields()
    {
        return [];
    }
    
    public function interfaces()
    {
        return [];
    }

    protected function getFieldResolver($name, $field)
    {
        if(isset($field['resolve']))
        {
            return $field['resolve'];
        }

        $resolveMethod = 'resolve'.studly_case($name).'Field';

        if(method_exists($this, $resolveMethod))
        {
            $resolver = array($this, $resolveMethod);
            return function() use ($resolver)
            {
                $args = func_get_args();
                return call_user_func_array($resolver, $args);
            };
        }
        
        return null;
    }
    
    public function getFields()
    {
        $fields = $this->fields();
        $allFields = [];
        foreach($fields as $name => $field)
        {
            if(is_string($field))
            {
                $field = app($field);
                $field->name = $name;
                $allFields[$name] = $field->toArray();
            }
            elseif ($field instanceof FieldDefinition)
            {
                $allFields[$field->name] = $field;
            }
            else
            {
                $resolver = $this->getFieldResolver($name, $field);
                if($resolver)
                {
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
    public function getAttributes()
    {
        $attributes = $this->attributes();
        $interfaces = $this->interfaces();
        
        $attributes = array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            }
        ], $attributes);
        
        if(sizeof($interfaces))
        {
            $attributes['interfaces'] = $interfaces;
        }

        return $attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }
    
    public function toType()
    {
        if($this->inputObject)
        {
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
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key]:null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]);
    }
    
}
