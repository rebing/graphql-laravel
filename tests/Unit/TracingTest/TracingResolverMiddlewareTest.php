<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

class TracingResolverMiddlewareTest extends TestCase
{
    public function testFieldTracingDisabledByDefault(): void
    {
        $driver = new FakeTracingDriver();

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        // field_tracing defaults to false
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertSame(0, $driver->startFieldCount);
    }

    public function testFieldTracingRecordsResolvers(): void
    {
        $driver = new FakeTracingDriver();

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertGreaterThan(0, $driver->startFieldCount);
        self::assertNotEmpty($driver->fieldNames);
    }

    public function testFieldTracingCallsDriverMethods(): void
    {
        $driver = new FakeTracingDriver();

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertGreaterThan(0, $driver->startFieldCount);
        self::assertSame($driver->startFieldCount, $driver->endFieldCount);
        self::assertContains('examples', $driver->fieldNames);
    }

    public function testFieldTracingNotRegisteredWhenDisabled(): void
    {
        $driver = new FakeTracingDriver();

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', false);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertSame(0, $driver->startFieldCount);
    }

    public function testFieldTracingContextPassedThrough(): void
    {
        $driver = new FakeTracingDriver(
            static fn (ResolveInfo $info): string => 'trace-context-' . $info->fieldName,
        );

        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);
        $this->app->instance(FakeTracingDriver::class, $driver);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        self::assertNotEmpty($driver->receivedContexts);

        // Verify the context from startFieldResolve was passed back to endFieldResolve
        foreach ($driver->receivedContexts as $ctx) {
            self::assertIsString($ctx);
            self::assertStringStartsWith('trace-context-', $ctx);
        }
    }
}
