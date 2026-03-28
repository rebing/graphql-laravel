<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use stdClass;

class ParentQuery extends Query
{
    protected $attributes = [
        'name' => 'parent',
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('Parent'));
    }

    public function resolve(): stdClass
    {
        $child = new stdClass;
        $child->public_name = 'public value';
        $child->secret_name = 'secret value';
        $child->allowed_name = 'allowed value';

        $parent = new stdClass;
        $parent->name = 'parent name';
        $parent->child = $child;

        return $parent;
    }
}
