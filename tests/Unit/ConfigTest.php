<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Objects\CustomExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\CustomExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ErrorFormatter;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Rebing\GraphQL\Tests\TestCase;

class ConfigTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql', [
            'prefix' => 'graphql_test',

            'routes' => [
                'query' => 'query/{graphql_schema?}',
                'mutation' => 'mutation/{graphql_schema?}',
            ],

            'default_schema' => 'custom',

            'schemas' => [
                'default' => [
                    'query' => [
                        'examples' => ExamplesQuery::class,
                    ],
                    'mutation' => [
                        'updateExample' => UpdateExampleMutation::class,
                    ],
                ],
                'custom' => [
                    'query' => [
                        'examplesCustom' => CustomExamplesQuery::class,
                    ],
                    'mutation' => [
                        'updateExampleCustom' => UpdateExampleMutation::class,
                    ],
                    'types' => [
                        CustomExampleType::class,
                    ],
                ],
                'shorthand' => BuildSchema::build('
                    schema {
                        query: ShorthandExample
                    }

                    type ShorthandExample {
                        echo(message: String!): String!
                    }
                '),
            ],

            'types' => [
                'Example' => ExampleType::class,
            ],

            'security' => [
                'query_max_complexity' => 1000,
                'query_max_depth' => 10,
            ],
        ]);
    }

    public function testRouteQuery(): void
    {
        $response = $this->call('GET', '/graphql_test/query', [
            'query' => $this->queries['examplesCustom'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
    }

    public function testRouteMutation(): void
    {
        $response = $this->call('POST', '/graphql_test/mutation', [
            'query' => $this->queries['updateExampleCustom'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
    }

    public function testTypes(): void
    {
        $types = GraphQL::getTypes();
        self::assertArrayHasKey('Example', $types);
    }

    public function testSchema(): void
    {
        $schema = GraphQL::schema();
        $schemaCustom = GraphQL::schema('custom');

        self::assertEquals($schema, $schemaCustom);
    }

    public function testSchemas(): void
    {
        $schemas = GraphQL::getSchemas();

        self::assertArrayHasKey('default', $schemas);
        self::assertArrayHasKey('custom', $schemas);
        self::assertArrayHasKey('shorthand', $schemas);
    }

    public function testSecurity(): void
    {
        /** @var QueryComplexity $queryComplexity */
        $queryComplexity = DocumentValidator::getRule('QueryComplexity');
        self::assertEquals(1000, $queryComplexity->getMaxQueryComplexity());

        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule('QueryDepth');
        self::assertEquals(10, $queryDepth->getMaxQueryDepth());
    }

    public function testErrorFormatter(): void
    {
        $error = $this->getMockBuilder(ErrorFormatter::class)
                    ->onlyMethods(['formatError'])
                    ->getMock();

        $error->expects(self::once())
            ->method('formatError');

        config([
            'graphql.error_formatter' => [$error, 'formatError'],
        ]);

        $this->graphql($this->queries['examplesWithError'], [
            'expectErrors' => true,
        ]);
    }
}
