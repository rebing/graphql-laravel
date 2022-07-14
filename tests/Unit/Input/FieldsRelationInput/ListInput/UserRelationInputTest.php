<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input\FieldsRelationInput\ListInput;

use Rebing\GraphQL\Tests\TestCase;

class UserRelationInputTest extends TestCase
{
    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/930
     */
    public function testInputValidationFieldsInList(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userRelationUpdate(
        data: [
            {
                input_type: null
                another_input_type: "yolo"
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
                                'data.0.input_type' => [
                                    'The data.0.input type field must have a value.',
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
                        'userRelationUpdate',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/930
     */
    public function testInputRelationFieldsInList(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userRelationUpdate(
        data: [
            {
                input_type: "yolo"
                another_input_type: "yolo"
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
                            'data.0.input_type' => [
                                'The data.0.input type field prohibits another input type from being present.',
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
                UserRelationUpdateMutation::class,
            ],
            'types' => [
                UserRelationInput::class,
            ],
        ]);
    }
}
