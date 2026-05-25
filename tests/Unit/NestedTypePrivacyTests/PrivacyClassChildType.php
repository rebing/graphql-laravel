<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use RuntimeException;

class PrivacyClassChildType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PrivacyClassChild',
    ];

    public function fields(): array
    {
        return [
            'public_name' => [
                'type' => Type::string(),
            ],
            'allowed_via_class' => [
                'type' => Type::string(),
                'privacy' => AllowPrivacy::class,
            ],
            'denied_via_class' => [
                'type' => Type::string(),
                'privacy' => DenyPrivacy::class,
            ],
            'allowed_with_resolver' => [
                'type' => Type::string(),
                'privacy' => AllowPrivacy::class,
                'resolve' => function (mixed $root): string {
                    return 'resolved-allowed';
                },
            ],
            'denied_with_resolver' => [
                'type' => Type::string(),
                'privacy' => DenyPrivacy::class,
                'resolve' => function (): string {
                    // Should never be called when privacy denies.
                    throw new RuntimeException('Resolver should not run when privacy denies');
                },
            ],
        ];
    }
}
