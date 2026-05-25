<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;
use RuntimeException;

class TracingExecutionMiddlewareTest extends TestCase
{
    public function testTracingMiddlewareCallsDriverStartAndEnd(): void
    {
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertNotNull($driver->startArgs);
        self::assertSame('default', $driver->startArgs['schemaName']);
        self::assertSame('query', $driver->startArgs['operationType']);
        self::assertNotNull($driver->startArgs['source']);
        self::assertTrue($driver->endCalled);
    }

    public function testTracingMiddlewareResolvesOperationTypeForMutation(): void
    {
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult(
            'mutation UpdateExample($test: String) { updateExample(test: $test) { test } }',
            ['test' => 'Hello'],
        );

        self::assertNotNull($driver->startArgs);
        self::assertSame('mutation', $driver->startArgs['operationType']);
    }

    public function testTracingMiddlewareResolvesOperationName(): void
    {
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult(
            $this->queries['examples'],
            null,
            ['operationName' => 'QueryExamples'],
        );

        self::assertNotNull($driver->startArgs);
        self::assertSame('QueryExamples', $driver->startArgs['operationName']);
    }

    public function testTracingMiddlewareNotAddedWhenDriverIsNull(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);

        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        // No tracing extensions should be set when tracing is disabled
        self::assertArrayNotHasKey('tracing', $result->extensions ?? []);
    }

    public function testTracingMiddlewareWorksWithCustomSchema(): void
    {
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult(
            $this->queries['examplesCustom'],
            null,
            ['schema' => 'custom'],
        );

        self::assertNotNull($driver->startArgs);
        self::assertSame('custom', $driver->startArgs['schemaName']);
    }

    public function testTracingMiddlewareCallsEndOperationWithErrorOnException(): void
    {
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);
        $this->app['config']->set('graphql.execution_middleware', [
            ThrowingExecutionMiddleware::class,
        ]);

        $caught = null;

        try {
            GraphQL::queryAndReturnResult($this->queries['examples']);
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        self::assertNotNull($caught, 'Expected RuntimeException to be re-thrown');
        self::assertSame('Test exception', $caught->getMessage());

        // endOperation() must have been called even though an exception was thrown
        self::assertNotNull($driver->startArgs);
        self::assertTrue($driver->endCalled);

        // The ExecutionResult passed to endOperation() must contain the exception as an error
        self::assertNotNull($driver->endResult);
        self::assertNotEmpty($driver->endResult->errors);
        self::assertSame('Test exception', $driver->endResult->errors[0]->getMessage());
        self::assertInstanceOf(RuntimeException::class, $driver->endResult->errors[0]->getPrevious());
    }

    public function testOperationTypeIsNullWhenQueryIsUnparseable(): void
    {
        // Covers the `try { $params->getParsedQuery(); } catch (Throwable)`
        // branch in `TracingExecutionMiddleware::resolveOperationType()`.
        // A syntactically invalid query cannot be parsed, so operationType
        // resolves to null but tracing still wraps the operation.
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult('{ parse(error) }');

        self::assertNotNull($driver->startArgs);
        self::assertNull($driver->startArgs['operationType']);
        self::assertTrue($driver->endCalled);
    }

    public function testOperationTypeIsNullWhenOperationNameDoesNotMatch(): void
    {
        // The document defines `query Foo { ... }` but we ask for `Bar`.
        // The middleware iterates definitions, sees the name mismatch
        // (`continue`) and reaches the end of the loop returning null.
        // This covers both the `continue;` branch (line 95) and the final
        // `return null;` (line 102) of `resolveOperationType()`.
        $driver = new FakeTracingDriver;

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult(
            'query Foo { examples { test } }',
            null,
            ['operationName' => 'Bar'],
        );

        self::assertNotNull($driver->startArgs);
        self::assertSame('Bar', $driver->startArgs['operationName']);
        self::assertNull($driver->startArgs['operationType']);
    }
}
