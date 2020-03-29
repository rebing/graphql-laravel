<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\AliasedRelationships;

use Illuminate\Database\Eloquent\Model;

class ModelRelationshipAdder
{
    /**
     * @param class-string<Model> $model
     * @param array<string,string> $relationships
     * @return void
     */
    public static function add(string $model, array $relationships): void
    {
        $model::addGlobalScope(new AddModelRelationshipScope($relationships));
    }
}
