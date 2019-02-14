<?php

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\UnionType as BaseUnionType;

class UnionType extends Type {

    public function types()
    {
        return [];
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        $types = $this->types();

        if (sizeof($types)) {
            $attributes['types'] = $types;
        }
        
        if(method_exists($this, 'resolveType'))
        {
            $attributes['resolveType'] = [$this, 'resolveType'];
        }
        
        return $attributes;
    }
    
    public function toType()
    {
        return new BaseUnionType($this->toArray());
    }
    
}
