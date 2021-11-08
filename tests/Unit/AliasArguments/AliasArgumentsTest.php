<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AliasArguments;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\ExampleNestedValidationInputObject;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\ExampleType;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\ExampleValidationInputObject;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\UpdateExampleMutation;

class AliasArgumentsTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                UpdateExampleMutation::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            ExampleType::class,
            ExampleValidationInputObject::class,
            ExampleNestedValidationInputObject::class,
        ]);
    }

    public function testMutationAlias(): void
    {
        $query = '
            mutation (
                $exampleValidationInputObject: ExampleValidationInputObject,
                $aList: [ExampleNestedValidationInputObject],
                $aListNonNull: [ExampleNestedValidationInputObject]!,
                $a_list_non_null_and_type_nonNull: [ExampleNestedValidationInputObject!]!,
                $a_list_type_nonNull: [ExampleNestedValidationInputObject!]
            ) {
                updateExample(
                    test_type_duplicates: null,
                    test: "HELLO",
                    test_with_alias_and_null: null,
                    test_type: $exampleValidationInputObject,
                    a_list: $aList,
                    a_list_non_null: $aListNonNull,
                    a_list_non_null_and_type_nonNull: $a_list_non_null_and_type_nonNull,
                    a_list_type_nonNull: $a_list_type_nonNull
                ) {
                    test
                }
            }
        ';

        $response = $this->httpGraphql($query, [
            'variables' => [
                'exampleValidationInputObject' => [
                    'nullValue' => null,
                    'val' => 22,
                    'nest' => [
                        'email' => 'test@mail.com',
                    ],
                    'list' => [
                        null,
                        [
                            'email' => 'test@mail.com',
                        ],
                    ],
                ],
                'aList' => [
                    [
                        'email' => 'test@mail.com',
                    ],
                ],
                'aListNonNull' => [
                    [
                        'email' => 'test@mail.com',
                    ],
                ],
                'a_list_non_null_and_type_nonNull' => [
                    [
                        'email' => 'test@mail.com',
                    ],
                ],
                'a_list_type_nonNull' => [
                    [
                        'email' => 'test@mail.com',
                    ],
                ],
            ],
        ]);

        $arguments = \Safe\json_decode($response['data']['updateExample']['test'], true);

        self::assertEquals([
            'test_with_alias_and_null' => null,
            'test_has_default_value' => 'DefaultValue123',
            'a_list' => [
                [
                    'email_alias' => 'test@mail.com',
                    'default_field_alias' => 'defcon',
                    'default_field_zero_string' => '',
                ],
            ],
            'a_list_non_null' => [
                [
                    'email_alias' => 'test@mail.com',
                    'default_field_alias' => 'defcon',
                    'default_field_zero_string' => '',
                ],
            ],
            'a_list_non_null_and_type_nonNull' => [
                [
                    'email_alias' => 'test@mail.com',
                    'default_field_alias' => 'defcon',
                    'default_field_zero_string' => '',
                ],
            ],
            'a_list_type_nonNull' => [
                [
                    'email_alias' => 'test@mail.com',
                    'default_field_alias' => 'defcon',
                    'default_field_zero_string' => '',
                ],
            ],
            'test_alias' => 'HELLO',
            'test_type_duplicates' => null,
            'test_type' => [
                'val_alias' => 22,
                'null_value' => null,
                'defaultValue_alias' => 'def',
                'nest' => [
                    'email_alias' => 'test@mail.com',
                    'default_field_alias' => 'defcon',
                    'default_field_zero_string' => '',
                ],
                'list' => [
                    null,
                    [
                        'email_alias' => 'test@mail.com',
                        'default_field_alias' => 'defcon',
                        'default_field_zero_string' => '',
                    ],
                ],
            ],
        ], $arguments);
    }
}
