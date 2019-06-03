<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests;

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\GraphQLServiceProvider;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\FieldDefinition;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesFilteredQuery;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Rebing\GraphQL\Tests\Support\Objects\ExampleFilterInputType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesAuthorizeQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesPaginationQuery;

class TestCase extends BaseTestCase
{
    protected $queries;
    protected $data;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queries = include __DIR__.'/Support/Objects/queries.php';
        $this->data = include __DIR__.'/Support/Objects/data.php';
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'examples'           => ExamplesQuery::class,
                'examplesAuthorize'  => ExamplesAuthorizeQuery::class,
                'examplesPagination' => ExamplesPaginationQuery::class,
                'examplesFiltered'   => ExamplesFilteredQuery::class,
            ],
            'mutation' => [
                'updateExample' => UpdateExampleMutation::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', [
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            'Example'            => ExampleType::class,
            'ExampleFilterInput' => ExampleFilterInputType::class,
        ]);

        $app['config']->set('app.debug', true);
    }

    protected function assertGraphQLSchema($schema): void
    {
        $this->assertInstanceOf(Schema::class, $schema);
    }

    protected function assertGraphQLSchemaHasQuery($schema, $key): void
    {
        // Query
        $query = $schema->getQueryType();
        $queryFields = $query->getFields();
        $this->assertArrayHasKey($key, $queryFields);

        $queryField = $queryFields[$key];
        $queryListType = $queryField->getType();
        $queryType = $queryListType->getWrappedType();
        $this->assertInstanceOf(FieldDefinition::class, $queryField);
        $this->assertInstanceOf(ListOfType::class, $queryListType);
        $this->assertInstanceOf(ObjectType::class, $queryType);
    }

    protected function assertGraphQLSchemaHasMutation($schema, $key): void
    {
        // Mutation
        $mutation = $schema->getMutationType();
        $mutationFields = $mutation->getFields();
        $this->assertArrayHasKey($key, $mutationFields);

        $mutationField = $mutationFields[$key];
        $mutationType = $mutationField->getType();
        $this->assertInstanceOf(FieldDefinition::class, $mutationField);
        $this->assertInstanceOf(ObjectType::class, $mutationType);
    }

    protected function getPackageProviders($app): array
    {
        return [
            GraphQLServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'GraphQL' => GraphQL::class,
        ];
    }
}
