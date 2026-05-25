<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use stdClass;

class PrivacyClassParentQuery extends Query
{
    protected $attributes = [
        'name' => 'parentWithClassPrivacy',
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('PrivacyClassParent'));
    }

    public function resolve(): stdClass
    {
        $child = new stdClass;
        $child->public_name = 'public value';
        $child->allowed_via_class = 'allowed value';
        $child->denied_via_class = 'denied value';
        $child->allowed_with_resolver = 'unused (resolver overrides)';
        $child->denied_with_resolver = 'unused';

        $parent = new stdClass;
        $parent->name = 'parent name';
        $parent->child = $child;

        return $parent;
    }
}
