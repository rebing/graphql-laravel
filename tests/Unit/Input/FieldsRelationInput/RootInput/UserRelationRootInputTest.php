<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Input\FieldsRelationInput\RootInput;

use Rebing\GraphQL\Tests\TestCase;

class UserRelationRootInputTest extends TestCase
{
    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/930
     */
    public function testInputValidationArgs(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userRelationRootUpdate(
        input_type: null
        another_input_type: "yolo"
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
                                'input_type' => [
                                    'The input type field must have a value.',
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
                        'userRelationRootUpdate',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * Ref https://github.com/rebing/graphql-laravel/issues/930
     */
    public function testInputRelationArgs(): void
    {
        $query = <<<'GRAQPHQL'
mutation {
    userRelationRootUpdate(
        input_type: "yolo"
        another_input_type: "yolo"
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
                            'input_type' => [
                                'The input type field prohibits another input type from being present.',
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
                        'userRelationRootUpdate',
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
                UserRelationRootUpdateMutation::class,
            ],
        ]);
    }
}
