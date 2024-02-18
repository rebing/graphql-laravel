<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Utils\SchemaPrinter;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesFilteredQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\TestCase;

class GraphQLQueryTest extends TestCase
{
    public function testQueryAndReturnResult(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertObjectHasAttribute('data', $result);

        self::assertEquals($result->data, [
            'examples' => $this->data,
        ]);
    }

    public function testQuery(): void
    {
        $resultArray = GraphQL::query($this->queries['examples']);
        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertArrayHasKey('data', $resultArray);
        self::assertEquals($resultArray['data'], $result->data);
    }

    public function testQueryAndReturnResultWithVariables(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithVariables'], [
            'index' => 0,
        ]);

        self::assertObjectHasAttribute('data', $result);
        self::assertCount(0, $result->errors);
        self::assertEquals($result->data, [
            'examples' => [
                $this->data[0],
            ],
        ]);
    }

    public function testQueryAndReturnResultWithFilterVariables(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithFilterVariables'], [
            'filter' => [
                'test' => 'Example 1',
            ],
        ]);

        self::assertObjectHasAttribute('data', $result);
        // When XDebug is used with breaking on exceptions the real error will
        // be visible in case the recursion for getInputTypeRules runs away.
        // GraphQL\Error\Error: Maximum function nesting level of '256' reached, aborting!
        self::assertCount(0, $result->errors);
        self::assertEquals($result->data, [
            'examplesFiltered' => [
                $this->data[0],
            ],
        ]);
    }

    public function testQueryAndReturnResultWithAuthorize(): void
    {
        $result = $this->httpGraphql($this->queries['examplesWithAuthorize'], [
            'expectErrors' => true,
        ]);
        self::assertNull($result['data']['examplesAuthorize']);
        self::assertEquals('Unauthorized', $result['errors'][0]['message']);
    }

    public function testQueryAndReturnResultWithCustomAuthorizeMessage(): void
    {
        $result = $this->httpGraphql($this->queries['examplesWithAuthorizeMessage'], [
            'expectErrors' => true,
        ]);
        self::assertNull($result['data']['examplesAuthorizeMessage']);
        self::assertEquals('You are not authorized to perform this action', $result['errors'][0]['message']);
    }

    /**
     * If an error was encountered before execution begins, the data entry should not be present in the result.
     */
    public function testQueryWithError(): void
    {
        $result = $this->httpGraphql($this->queries['examplesWithError'], [
            'expectErrors' => true,
        ]);

        self::assertArrayHasKey('errors', $result);
        self::assertCount(1, $result['errors']);
        self::assertArrayHasKey('message', $result['errors'][0]);
        self::assertArrayHasKey('locations', $result['errors'][0]);
    }

    public function testQueryWithValidationError(): void
    {
        $result = $this->httpGraphql($this->queries['examplesWithValidation'], [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'test_validation.args.index' => [
                                'The test validation.args.index field is required.',
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 13,
                        ],
                    ],
                    'path' => [
                        'examples',
                    ],
                ],
            ],
            'data' => [
                'examples' => null,
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testQueryWithValidation(): void
    {
        $result = $this->httpGraphql($this->queries['examplesWithValidation'], [
            'variables' => [
                'index' => 0,
            ],
        ]);

        self::assertArrayHasKey('data', $result);
        self::assertArrayNotHasKey('errors', $result);
    }

    public function testCustomDefaultFieldResolverStaticClass(): void
    {
        $this->app['config']->set('graphql.defaultFieldResolver', [static::class, 'exampleDefaultFieldResolverForTest']);

        $schema = GraphQL::buildSchemaFromConfig([
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
        ]);
        GraphQL::addSchema('default', $schema);

        $result = GraphQL::queryAndReturnResult($this->queries['examplesCustom']);

        $expectedDataResult = [
            'examplesCustom' => [
                [
                    'test' => 'defaultFieldResolver static method value',
                ],
                [
                    'test' => 'defaultFieldResolver static method value',
                ],
                [
                    'test' => 'defaultFieldResolver static method value',
                ],
            ],
        ];
        self::assertSame($expectedDataResult, $result->data);
        self::assertCount(0, $result->errors);
    }

    public static function exampleDefaultFieldResolverForTest(): string
    {
        return 'defaultFieldResolver static method value';
    }

    public function testCustomDefaultFieldResolverClosure(): void
    {
        $this->app['config']->set('graphql.defaultFieldResolver', function () {
            return 'defaultFieldResolver closure value';
        });

        $schema = GraphQL::buildSchemaFromConfig([
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
        ]);
        GraphQL::addSchema('default', $schema);

        $result = GraphQL::queryAndReturnResult($this->queries['examplesCustom']);

        $expectedDataResult = [
            'examplesCustom' => [
                [
                    'test' => 'defaultFieldResolver closure value',
                ],
                [
                    'test' => 'defaultFieldResolver closure value',
                ],
                [
                    'test' => 'defaultFieldResolver closure value',
                ],
            ],
        ];
        self::assertSame($expectedDataResult, $result->data);
        self::assertCount(0, $result->errors);
    }

    public function testPrintSchema(): void
    {
        $schema = GraphQL::buildSchemaFromConfig([
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
        ]);

        $gql = SchemaPrinter::doPrint($schema);

        $queryFragment = 'type Query {
  examplesCustom(index: Int): [Example]
}';

        self::assertStringContainsString($queryFragment, $gql);
    }

    public function testOtherQueryClassesArentInitializedUsingNamedSchemaConfig(): void
    {
        $this->app->afterResolving(ExamplesFilteredQuery::class, function (): void {
            $this->fail('Only requested query should become initialized.');
        });

        $schema = GraphQL::buildSchemaFromConfig([
            'query' => [
                'examples' => ExamplesQuery::class,
                'examplesFiltered' => ExamplesFilteredQuery::class,
            ],
        ]);
        GraphQL::addSchema('default', $schema);

        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        $expectedDataResult = [
            'examples' => [
                [
                    'test' => 'Example 1',
                ],
                [
                    'test' => 'Example 2',
                ],
                [
                    'test' => 'Example 3',
                ],
            ],
        ];
        self::assertSame($expectedDataResult, $result->data);
    }
}
