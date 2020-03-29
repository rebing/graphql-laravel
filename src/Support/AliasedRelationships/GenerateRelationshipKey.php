<?php
namespace Rebing\GraphQL\Support\AliasedRelationships;

class GenerateRelationshipKey
{    
    const RELATIONSHIP_PRE_TEXT = '_generated_';
    public static function generate(string $key): string
    {
        return self::RELATIONSHIP_PRE_TEXT . $key;
    }

}
