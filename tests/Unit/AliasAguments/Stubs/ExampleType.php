<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\AliasAguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExampleType extends GraphQLType
{
    public const TYPE = 'Example';

    protected $attributes = [
        'name' => self::TYPE,
        'description' => 'An example',
    ];

    public function fields(): array
    {
        return [
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field',
            ],
        ];
    }
}
