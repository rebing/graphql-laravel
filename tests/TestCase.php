<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\ExpectationFailedException;
use Rebing\GraphQL\GraphQLServiceProvider;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Objects\ExampleFilterInputType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesAuthorizeMessageQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesAuthorizeQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleSchema;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesFilteredQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesMiddlewareQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesPaginationQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Symfony\Component\Console\Tester\CommandTester;

class TestCase extends BaseTestCase
{
    protected $queries;
    protected $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queries = include __DIR__ . '/Support/Objects/queries.php';
        $this->data = include __DIR__ . '/Support/Objects/data.php';
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'examples' => ExamplesQuery::class,
                'examplesAuthorize' => ExamplesAuthorizeQuery::class,
                'examplesAuthorizeMessage' => ExamplesAuthorizeMessageQuery::class,
                'examplesMiddleware' => ExamplesMiddlewareQuery::class,
                'examplesPagination' => ExamplesPaginationQuery::class,
                'examplesFiltered' => ExamplesFilteredQuery::class,
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

        $app['config']->set('graphql.schemas.class_based', ExampleSchema::class);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
            'ExampleFilterInput' => ExampleFilterInputType::class,
        ]);

        $app['config']->set('app.debug', true);
    }

    protected function assertGraphQLSchema($schema): void
    {
        self::assertInstanceOf(Schema::class, $schema);
    }

    protected function assertGraphQLSchemaHasQuery($schema, $key): void
    {
        // Query
        $query = $schema->getQueryType();
        $queryFields = $query->getFields();
        self::assertArrayHasKey($key, $queryFields);

        $queryField = $queryFields[$key];
        $queryListType = $queryField->getType();
        $queryType = $queryListType->getWrappedType();
        self::assertInstanceOf(FieldDefinition::class, $queryField);
        self::assertInstanceOf(ListOfType::class, $queryListType);
        self::assertInstanceOf(ObjectType::class, $queryType);
    }

    protected function assertGraphQLSchemaHasMutation($schema, $key): void
    {
        // Mutation
        $mutation = $schema->getMutationType();
        $mutationFields = $mutation->getFields();
        self::assertArrayHasKey($key, $mutationFields);

        $mutationField = $mutationFields[$key];
        $mutationType = $mutationField->getType();
        self::assertInstanceOf(FieldDefinition::class, $mutationField);
        self::assertInstanceOf(ObjectType::class, $mutationType);
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

    /**
     * The `CommandTester` is directly returned, use methods like
     * `->getDisplay()` or `->getStatusCode()` on it.
     *
     * @param array<string,mixed> $arguments The command line arguments, array of key=>value
     *                                       Examples:
     *                                       - named  arguments: ['model' => 'Post']
     *                                       - boolean flags: ['--all' => true]
     *                                       - arguments with values: ['--arg' => 'value']
     * @param array<string,mixed> $interactiveInput Interactive responses to the command
     *                                              I.e. anything the command `->ask()` or `->confirm()`, etc.
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
     * Helper to dispatch an HTTP GraphQL requests.
     *
     * @param array<string,mixed> $options
     *                                     Supports the following options:
     *                                     - `expectErrors` (default: false): if no errors are expected but present, let's the test fail
     *                                     - `httpStatusCode` (default: 200): the HTTP status code to expect
     *                                     - `variables` (default: null): GraphQL variables for the query
     *                                     - `schemaName` (default: null): GraphQL schema to use
     * @return array<string,mixed> GraphQL result
     */
    protected function httpGraphql(string $query, array $options = []): array
    {
        $expectedHttpStatusCode = $options['httpStatusCode'] ?? 200;
        $expectErrors = $options['expectErrors'] ?? false;
        $variables = $options['variables'] ?? null;
        $schemaName = $options['schemaName'] ?? null;

        $payload = [
            'query' => $query,
        ];

        if ($variables) {
            $payload['variables'] = $variables;
        }

        $path = '/graphql';

        if ($schemaName) {
            $path .= "/$schemaName";
        }

        /** @var JsonResponse $response */
        $response = $this->json('POST', $path, $payload);

        $httpStatusCode = $response->getStatusCode();

        if ($expectedHttpStatusCode !== $httpStatusCode) {
            $result = $response->getData(true);
            $msg = var_export($result, true) . "\n";
            self::assertSame($expectedHttpStatusCode, $httpStatusCode, $msg);
        }

        $result = $response->getData(true);

        $assertMessage = null;

        if (!$expectErrors && isset($result['errors'])) {
            $appendErrors = '';

            if (isset($result['errors'][0]['trace'])) {
                $appendErrors = "\n\n" . $this->formatSafeTrace($result['errors'][0]['trace']);
            }

            $assertMessage = "Probably unexpected error in GraphQL response:\n"
                . var_export($result, true)
                . $appendErrors;
        }
        unset($result['errors'][0]['trace']);

        if ($assertMessage) {
            throw new ExpectationFailedException($assertMessage);
        }

        return $result;
    }

    /**
     * Converts the trace as generated from \GraphQL\Error\FormattedError::toSafeTrace
     * to a more human-readable string for a failed test.
     */
    private function formatSafeTrace(array $trace): string
    {
        return implode(
            "\n",
            array_map(static function (array $row, int $index): string {
                $line = "#$index ";
                $line .= $row['file'] ?? '';

                if (isset($row['line'])) {
                    $line .= "({$row['line']}) :";
                }

                if (isset($row['call'])) {
                    $line .= ' ' . $row['call'];
                }

                if (isset($row['function'])) {
                    $line .= ' ' . $row['function'];
                }

                return $line;
            }, $trace, array_keys($trace))
        );
    }

    /**
     * @todo Remove this method once we're PHPUnit 9+ only.
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        self::assertThat($string, new RegularExpression($pattern), $message);
    }
}
