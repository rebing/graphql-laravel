<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\ExpectationFailedException;
use Rebing\GraphQL\GraphQLServiceProvider;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Objects\ExampleFilterInputType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesAuthorizeMessageQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesAuthorizeQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesConfigAliasQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesFilteredQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesMiddlewareQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesPaginationQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType2;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Symfony\Component\Console\Tester\CommandTester;

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
        if (env('TESTS_ENABLE_LAZYLOAD_TYPES') === '1') {
            $app['config']->set('graphql.lazyload_types', true);
        }

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'examples' => ExamplesQuery::class,
                'examplesAuthorize' => ExamplesAuthorizeQuery::class,
                'examplesAuthorizeMessage' => ExamplesAuthorizeMessageQuery::class,
                'examplesMiddleware' => ExamplesMiddlewareQuery::class,
                'examplesPagination' => ExamplesPaginationQuery::class,
                'examplesFiltered' => ExamplesFilteredQuery::class,
                'examplesConfigAlias' => ExamplesConfigAliasQuery::class,
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
            'Example' => ExampleType::class,
            'ExampleConfigAlias' => ExampleType2::class,
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
        $providers = [
            GraphQLServiceProvider::class,
        ];

        return $providers;
    }

    protected function getPackageAliases($app): array
    {
        return [
            'GraphQL' => GraphQL::class,
        ];
    }

    /**
     * The `CommandTester` is directly returned, use methods like
     * `->getDisplay()` or `->getStatusCode()` on it.
     *
     * @param Command $command
     * @param array $arguments The command line arguments, array of key=>value
     *   Examples:
     *   - named  arguments: ['model' => 'Post']
     *   - boolean flags: ['--all' => true]
     *   - arguments with values: ['--arg' => 'value']
     * @param array $interactiveInput Interactive responses to the command
     *   I.e. anything the command `->ask()` or `->confirm()`, etc.
     * @return CommandTester
     */
    protected function runCommand(Command $command, array $arguments = [], array $interactiveInput = []): CommandTester
    {
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->setInputs($interactiveInput);

        $tester->execute($arguments);

        return $tester;
    }

    /**
     * Helper to dispatch an internal GraphQL requests.
     *
     * @param  string  $query
     * @param  array  $options
     *   Supports the following options:
     *   - `expectErrors` (default: false): if no errors are expected but present, let's the test fail
     *   - `variables` (default: null): GraphQL variables for the query
     * @return array GraphQL result
     */
    protected function graphql(string $query, array $options = []): array
    {
        $expectErrors = $options['expectErrors'] ?? false;
        $variables = $options['variables'] ?? null;

        $result = GraphQL::query($query, $variables);

        $assertMessage = null;

        if (! $expectErrors && isset($result['errors'])) {
            $appendErrors = '';
            if (isset($result['errors'][0]['trace'])) {
                $appendErrors = "\n\n".$this->formatSafeTrace($result['errors'][0]['trace']);
            }

            $assertMessage = "Probably unexpected error in GraphQL response:\n"
                .var_export($result, true)
                .$appendErrors;
        }
        unset($result['errors'][0]['trace']);

        if ($assertMessage) {
            throw new ExpectationFailedException($assertMessage);
        }

        return $result;
    }

    /**
     * Helper to dispatch an HTTP GraphQL requests.
     *
     * @param  string  $query
     * @param  array  $options
     *   Supports the following options:
     *   - `httpStatusCode` (default: 200): the HTTP status code to expect
     * @return array GraphQL result
     */
    protected function httpGraphql(string $query, array $options = []): array
    {
        $expectedHttpStatusCode = $options['httpStatusCode'] ?? 200;

        $response = $this->call('GET', '/graphql', [
            'query' => $query,
        ]);

        $httpStatusCode = $response->getStatusCode();

        if ($expectedHttpStatusCode !== $httpStatusCode) {
            $result = $response->getData(true);
            $msg = var_export($result, true)."\n";
            $this->assertSame($expectedHttpStatusCode, $httpStatusCode, $msg);
        }

        return $response->getData(true);
    }

    /**
     * Converts the trace as generated from \GraphQL\Error\FormattedError::toSafeTrace
     * to a more human-readable string for a failed test.
     *
     * @param array $trace
     * @return string
     */
    private function formatSafeTrace(array $trace): string
    {
        return implode(
            "\n",
            array_map(function (array $row, int $index): string {
                $line = "#$index ";
                $line .= $row['file'] ?? '';
                if (isset($row['line'])) {
                    $line .= "({$row['line']}) :";
                }
                if (isset($row['call'])) {
                    $line .= ' '.$row['call'];
                }
                if (isset($row['function'])) {
                    $line .= ' '.$row['function'];
                }

                return $line;
            }, $trace, array_keys($trace))
        );
    }
}
