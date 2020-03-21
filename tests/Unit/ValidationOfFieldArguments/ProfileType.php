<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ProfileType extends GraphQLType
{
    /**
     * @var array<string,string>
     */
    protected $attributes = [
        'name' => 'ProfileType',
        'description' => 'An example',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::string(),
                'description' => 'A test field',
                'args' => [
                    'includeMiddleNames' => [
                        'type' => Type::string(),
                        'rules' => ['regex:/^(yes|no)$/i'],
                    ],
                ],
            ],
            'height' => [
                'type' => Type::string(),
                'description' => 'A test field',
                'args' => [
                    'unit' => [
                        'type' => Type::string(),
                        'rules' => function ($args) {
                            Assert::assertSame([
                                'unit' => 'not_correct',
                            ], $args);

                            return ['regex:/^inch|cm$/i'];
                        },
                    ],
                ],
            ],
        ];
    }
}
