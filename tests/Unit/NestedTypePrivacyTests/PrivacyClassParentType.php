<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PrivacyClassParentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PrivacyClassParent',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'child' => [
                'type' => GraphQL::type('PrivacyClassChild'),
            ],
        ];
    }
}
