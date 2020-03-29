<?php
namespace Rebing\GraphQL\Support\AliasedRelationships;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AddModelRelationshipScope implements Scope
{    
    /**
     * @var array<string,string>
     */
    private $relationships;

    /**
     * @param array<string,string> $relationships
     */
    public function __construct(array $relationships = [])
    {
        $this->relationships = $relationships;
    }

    public function extend(Builder $builder): void {
        foreach($this->relationships as [$graphqlAlias, $relationship]) {
            $builder->macro($graphqlAlias, function (Builder $builder) use($relationship) {
                return $builder->getModel()->{$relationship}();
            });
        }
    }

    public function apply(Builder $builder, Model $model) {
    
    }
    
}
