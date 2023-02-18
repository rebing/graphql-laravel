<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input;

use Rebing\GraphQL\Tests\TestCase;

class UserInputTest extends TestCase
{
    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/716
     */
    public function testWildcardInputValidationOneInput(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userUpdate(
        data: [
            {
                password: "yolo"
                password_confirmation: "yolo"
            }
        ]
    )
}
GRAQPHQL;

        $result = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'data.0.password' => [
                                // The data.0.password and data.*.password confirmation must match.
                                trans('validation.same', ['attribute' => 'data.0.password', 'other' => 'data.*.password confirmation']),
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 5,
                        ],
                    ],
                    'path' => [
                        'userUpdate',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/716
     */
    public function testWildcardInputValidationTwoInputs(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userUpdate(
        data: [
            {
                password: "yolo"
                password_confirmation: "yolo"
            }
            {
                password: "foo"
                password_confirmation: "foo"
            }
        ]
    )
}
GRAQPHQL;

        $result = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'data.0.password' => [
                                // The data.0.password and data.*.password confirmation must match.
                                trans('validation.same', ['attribute' => 'data.0.password', 'other' => 'data.*.password confirmation']),
                            ],
                            'data.1.password' => [
                                // The data.1.password and data.*.password confirmation must match.
                                trans('validation.same', ['attribute' => 'data.1.password', 'other' => 'data.*.password confirmation']),
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 5,
                        ],
                    ],
                    'path' => [
                        'userUpdate',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                UserUpdateMutation::class,
            ],
            'types' => [
                UserInput::class,
            ],
        ]);
    }
}
