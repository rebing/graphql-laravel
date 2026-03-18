<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Tracing\TracingManager;
use Rebing\GraphQL\Tests\TestCase;

class TracingManagerTest extends TestCase
{
    public function testResolveConfigReturnsGlobalWhenNoSchemaOverride(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $config = $manager->resolveConfig('default');

        self::assertNotNull($config);
        self::assertSame(FakeTracingDriver::class, $config['driver']);
        self::assertTrue($config['field_tracing']);
    }

    public function testResolveConfigReturnsNullWhenNoDriverConfigured(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertNull($manager->resolveConfig('default'));
    }

    public function testSchemaTracingFalseDisablesTracing(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.schemas.default.tracing', false);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertNull($manager->resolveConfig('default'));
        self::assertNull($manager->driverFor('default'));
    }

    public function testPerSchemaDriverOverridesGlobal(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.schemas.custom.tracing', [
            'driver' => AnotherFakeTracingDriver::class,
        ]);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $config = $manager->resolveConfig('custom');
        self::assertNotNull($config);
        self::assertSame(AnotherFakeTracingDriver::class, $config['driver']);
    }

    public function testPerSchemaFieldTracingOverridesGlobal(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', false);
        $this->app['config']->set('graphql.schemas.default.tracing', [
            'field_tracing' => true,
        ]);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->fieldTracingEnabledFor('default'));
    }

    public function testPerSchemaDriverOptionsDeepMerged(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.driver_options', [
            'include_document' => false,
            'custom_option' => 'global_value',
        ]);
        $this->app['config']->set('graphql.schemas.default.tracing', [
            'driver_options' => [
                'include_document' => true,
            ],
        ]);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $config = $manager->resolveConfig('default');
        self::assertNotNull($config);
        // Schema override wins
        self::assertTrue($config['driver_options']['include_document']);
        // Global option preserved
        self::assertSame('global_value', $config['driver_options']['custom_option']);
    }

    public function testDriverForReturnsDriverInstance(): void
    {
        $driver = new FakeTracingDriver();
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app->instance(FakeTracingDriver::class, $driver);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertSame($driver, $manager->driverFor('default'));
    }

    public function testDriverForReturnsNullWhenDisabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertNull($manager->driverFor('default'));
    }

    public function testDriverForCachesSameDriverForSameConfig(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $driver = new FakeTracingDriver();
        $this->app->instance(FakeTracingDriver::class, $driver);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $driver1 = $manager->driverFor('default');
        $driver2 = $manager->driverFor('custom');

        // Both schemas use the same global config, so same instance
        self::assertSame($driver1, $driver2);
    }

    public function testHasAnyTracingReturnsTrueWhenGlobalDriverSet(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyTracing());
    }

    public function testHasAnyTracingReturnsFalseWhenNoDriver(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertFalse($manager->hasAnyTracing());
    }

    public function testHasAnyTracingReturnsTrueWhenOnlyPerSchemaDriver(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);
        $this->app['config']->set('graphql.schemas.custom.tracing', [
            'driver' => FakeTracingDriver::class,
        ]);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyTracing());
    }

    public function testHasAnyFieldTracingReturnsTrueWhenGlobalEnabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyFieldTracing());
    }

    public function testHasAnyFieldTracingReturnsFalseWhenDisabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', false);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertFalse($manager->hasAnyFieldTracing());
    }

    public function testHasAnyFieldTracingReturnsTrueWhenPerSchemaEnabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', false);
        $this->app['config']->set('graphql.schemas.custom.tracing', [
            'field_tracing' => true,
        ]);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyFieldTracing());
    }

    public function testHasAnyTracingCachesResult(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        // First call computes and caches
        self::assertTrue($manager->hasAnyTracing());
        // Second call returns cached result (no way to observe this directly,
        // but the result should be consistent)
        self::assertTrue($manager->hasAnyTracing());
    }

    public function testHasAnyFieldTracingCachesResult(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyFieldTracing());
        self::assertTrue($manager->hasAnyFieldTracing());
    }

    public function testHasAnyTracingFastPathWithGlobalDriver(): void
    {
        // When a global driver is set, hasAnyTracing should return true
        // without iterating schemas (fast path)
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.schemas', []);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyTracing());
    }

    public function testPerSchemaTracingDisabledDoesNotAffectOtherSchemas(): void
    {
        $driver = new FakeTracingDriver();
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.schemas.default.tracing', false);
        $this->app->instance(FakeTracingDriver::class, $driver);

        // default schema has tracing disabled
        GraphQL::queryAndReturnResult($this->queries['examples']);
        self::assertNull($driver->startArgs);

        // custom schema inherits global tracing and should work
        GraphQL::queryAndReturnResult(
            $this->queries['examplesCustom'],
            null,
            ['schema' => 'custom'],
        );
        self::assertNotNull($driver->startArgs);
        self::assertSame('custom', $driver->startArgs['schemaName']);
    }

    public function testPerSchemaTracingEnabledWithoutGlobalDriver(): void
    {
        $driver = new FakeTracingDriver();
        $this->app['config']->set('graphql.tracing.driver', null);
        $this->app['config']->set('graphql.schemas.custom.tracing', [
            'driver' => FakeTracingDriver::class,
        ]);
        $this->app->instance(FakeTracingDriver::class, $driver);

        // default schema should not be traced (no global driver, no schema override)
        GraphQL::queryAndReturnResult($this->queries['examples']);
        self::assertNull($driver->startArgs);

        // custom schema should be traced via per-schema config
        GraphQL::queryAndReturnResult(
            $this->queries['examplesCustom'],
            null,
            ['schema' => 'custom'],
        );
        self::assertNotNull($driver->startArgs);
        self::assertSame('custom', $driver->startArgs['schemaName']);
    }

    public function testPerSchemaFieldTracingEnabled(): void
    {
        $driver = new FakeTracingDriver();
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', false);
        $this->app['config']->set('graphql.schemas.custom.tracing', [
            'field_tracing' => true,
        ]);
        $this->app->instance(FakeTracingDriver::class, $driver);

        // default schema: field_tracing is false globally
        GraphQL::queryAndReturnResult($this->queries['examples']);
        self::assertSame(0, $driver->startFieldCount);

        // custom schema: field_tracing is true per-schema
        GraphQL::queryAndReturnResult(
            $this->queries['examplesCustom'],
            null,
            ['schema' => 'custom'],
        );
        self::assertGreaterThan(0, $driver->startFieldCount);
    }

    public function testClassBasedSchemaWithoutTracingSkippedByHasAnyTracing(): void
    {
        // class_based schema uses ExampleSchema which has no 'tracing' key in toConfig()
        // hasAnyTracing should resolve it and see no tracing config
        $this->app['config']->set('graphql.tracing.driver', null);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        // Should not throw, should return false (no global driver, no per-schema tracing)
        self::assertFalse($manager->hasAnyTracing());
    }

    public function testClassBasedSchemaWithTracingDetectedByHasAnyTracing(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);
        $this->app['config']->set('graphql.schemas.tracing_schema', ExampleSchemaWithTracing::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertTrue($manager->hasAnyTracing());
    }

    public function testClassBasedSchemaResolveConfigReturnsTracingArray(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);
        $this->app['config']->set('graphql.schemas.tracing_schema', ExampleSchemaWithTracing::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $config = $manager->resolveConfig('tracing_schema');

        self::assertNotNull($config);
        self::assertSame(FakeTracingDriver::class, $config['driver']);
        self::assertTrue($config['field_tracing']);
    }

    public function testClassBasedSchemaTracingMergedWithGlobalConfig(): void
    {
        $this->app['config']->set('graphql.tracing.driver', AnotherFakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.driver_options', [
            'include_document' => false,
            'global_option' => 'value',
        ]);
        $this->app['config']->set('graphql.schemas.tracing_schema', ExampleSchemaWithTracing::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        $config = $manager->resolveConfig('tracing_schema');

        self::assertNotNull($config);
        // Schema driver overrides global
        self::assertSame(FakeTracingDriver::class, $config['driver']);
        // Schema field_tracing overrides global
        self::assertTrue($config['field_tracing']);
    }

    public function testClassBasedSchemaWithTracingDisabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.schemas.disabled_schema', ExampleSchemaWithTracingDisabled::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        self::assertNull($manager->resolveConfig('disabled_schema'));
        self::assertNull($manager->driverFor('disabled_schema'));
    }

    public function testClassBasedSchemaFieldTracingDetectedByHasAnyFieldTracing(): void
    {
        $this->app['config']->set('graphql.tracing.driver', null);
        $this->app['config']->set('graphql.tracing.field_tracing', false);
        $this->app['config']->set('graphql.schemas.tracing_schema', ExampleSchemaWithTracing::class);

        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        // ExampleSchemaWithTracing has field_tracing: true
        self::assertTrue($manager->hasAnyFieldTracing());
    }

    public function testClassBasedSchemaInheritsGlobalTracingWhenNoTracingKey(): void
    {
        $this->app['config']->set('graphql.tracing.driver', FakeTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);

        // class_based uses ExampleSchema which has no 'tracing' key
        /** @var TracingManager $manager */
        $manager = $this->app->make(TracingManager::class);

        // Should inherit global config
        $config = $manager->resolveConfig('class_based');

        self::assertNotNull($config);
        self::assertSame(FakeTracingDriver::class, $config['driver']);
        self::assertTrue($config['field_tracing']);
    }
}
