<?php
namespace Rebing\GraphQL\Support\AliasedRelationships;

use Rebing\GraphQL\Support\AliasedRelationships\AddModelRelationshipScope;

class ModelRelationshipAdder
{
    /**
     * Undocumented function
     *
     * @param string $model
     * @param array<string,string> $relationships
     * @return void
     */
    public static function add(string $model, array $relationships): void
    {
        $model::addGlobalScope(new AddModelRelationshipScope($relationships));
    }
}
