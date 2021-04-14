<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AccountType extends GraphQLType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'AccountType',
        'description' => 'An example',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::int(),
                'description' => 'A test field',
            ],
            'profile' => [
                'type' => GraphQL::type('ProfileType'),
                'args' => [
                    'profileId' => [
                        'type' => Type::int(),
                        'rules' => function ($args) {
                            Assert::assertSame([
                                'profileId' => 100,
                            ], $args);

                            return ['required', 'integer', 'max:10'];
                        },
                    ],
                ],
            ],
            'alias' => [
                'type' => Type::string(),
                'args' => [
                    'type' => [
                        'type' => Type::string(),
                        'rules' => function ($args) {
                            return ['regex:/^l33t|normal$/i'];
                        },
                    ],
                ],
            ],
        ];
    }
}
