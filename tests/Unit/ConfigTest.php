<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

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

        $this->app['config']->set([
            'graphql.error_formatter' => [$error, 'formatError'],
        ]);

        $this->httpGraphql($this->queries['examplesWithError'], [
            'expectErrors' => true,
        ]);
    }
}
