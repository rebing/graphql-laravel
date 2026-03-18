<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Rebing\GraphQL\Support\Contracts\ConfigConvertible;

/**
 * Manages tracing configuration and driver instances across schemas.
 *
 * Supports both global and per-schema tracing configuration. Per-schema
 * settings are merged over the global config (schema values win), with
 * deep-merging for the `driver_options` key.
 *
 * Driver instances are cached by (driver class + driver_options) so that
 * schemas sharing the same configuration reuse the same driver instance.
 */
class TracingManager
{
    /** @var array<string, TracingDriver> */
    private array $drivers = [];

    private ?bool $cachedHasAnyTracing = null;

    private ?bool $cachedHasAnyFieldTracing = null;

    public function __construct(
        private readonly Container $app,
        private readonly Repository $config,
    ) {
    }

    /**
     * Resolve the effective tracing config for a schema.
     *
     * Per-schema `tracing` config is merged over the global `tracing` config.
     * A schema may set `'tracing' => false` to explicitly disable tracing.
     *
     * Supports class-based schemas implementing `ConfigConvertible`: the class
     * is resolved from the container, `toConfig()` is called, and the `tracing`
     * key is extracted.
     *
     * @return array<string, mixed>|null The merged config, or null if tracing is disabled
     */
    public function resolveConfig(string $schemaName): ?array
    {
        /** @var array<string, mixed>|false|null $schemaTracing */
        $schemaTracing = $this->resolveSchemaTracing($schemaName);

        if (false === $schemaTracing) {
            return null;
        }

        /** @var array<string, mixed> $globalConfig */
        $globalConfig = $this->config->get('graphql.tracing', []);

        if (!\is_array($schemaTracing)) {
            // No schema-level override — use global config as-is
            return null !== ($globalConfig['driver'] ?? null) ? $globalConfig : null;
        }

        // Merge: schema-level values override global
        /** @var array<string, mixed> $merged */
        $merged = array_replace($globalConfig, $schemaTracing);

        // Deep-merge driver_options so schemas can override individual options
        if (isset($globalConfig['driver_options']) || isset($schemaTracing['driver_options'])) {
            /** @var array<string, mixed> $globalOptions */
            $globalOptions = (array) ($globalConfig['driver_options'] ?? []);
            /** @var array<string, mixed> $schemaOptions */
            $schemaOptions = (array) ($schemaTracing['driver_options'] ?? []);
            $merged['driver_options'] = array_replace($globalOptions, $schemaOptions);
        }

        return null !== ($merged['driver'] ?? null) ? $merged : null;
    }

    /**
     * Get the tracing driver for a given schema.
     *
     * Returns null if tracing is disabled for this schema.
     */
    public function driverFor(string $schemaName): ?TracingDriver
    {
        $config = $this->resolveConfig($schemaName);

        if (null === $config) {
            return null;
        }

        /** @var class-string<TracingDriver>|null $driverClass */
        $driverClass = $config['driver'] ?? null;

        if (null === $driverClass) {
            return null;
        }

        /** @var array<string, mixed> $driverOptions */
        $driverOptions = (array) ($config['driver_options'] ?? []);
        $cacheKey = $driverClass . ':' . serialize($driverOptions);

        if (!isset($this->drivers[$cacheKey])) {
            // When the container already has an explicit binding (e.g. an
            // instance registered in tests), honor it and resolve without
            // extra parameters.  Container::make() with extra parameters
            // bypasses instance bindings.
            /** @var TracingDriver $driver */
            $driver = $this->app->bound($driverClass)
                ? $this->app->make($driverClass)
                : $this->app->make($driverClass, ['driverOptions' => $driverOptions]);
            $this->drivers[$cacheKey] = $driver;
        }

        return $this->drivers[$cacheKey];
    }

    /**
     * Check if field tracing is enabled for a given schema.
     */
    public function fieldTracingEnabledFor(string $schemaName): bool
    {
        $config = $this->resolveConfig($schemaName);

        return null !== $config && true === ($config['field_tracing'] ?? false);
    }

    /**
     * Check whether any configured schema has tracing enabled.
     *
     * Used at boot time and when building the execution middleware list.
     * The result is cached for the lifetime of the manager instance.
     */
    public function hasAnyTracing(): bool
    {
        if (null !== $this->cachedHasAnyTracing) {
            return $this->cachedHasAnyTracing;
        }

        // Fast path: a global driver means at least global schemas are traced
        if (null !== $this->config->get('graphql.tracing.driver')) {
            return $this->cachedHasAnyTracing = true;
        }

        // Slow path: check per-schema overrides
        /** @var array<string, mixed> $schemas */
        $schemas = $this->config->get('graphql.schemas', []);

        foreach (array_keys($schemas) as $schemaName) {
            if (null !== $this->resolveConfig((string) $schemaName)) {
                return $this->cachedHasAnyTracing = true;
            }
        }

        return $this->cachedHasAnyTracing = false;
    }

    /**
     * Check whether any configured schema has field tracing enabled.
     *
     * Used at boot time to decide whether to register the resolver middleware.
     * The result is cached for the lifetime of the manager instance.
     */
    public function hasAnyFieldTracing(): bool
    {
        if (null !== $this->cachedHasAnyFieldTracing) {
            return $this->cachedHasAnyFieldTracing;
        }

        // Fast path: global driver + global field_tracing
        if (null !== $this->config->get('graphql.tracing.driver') &&
            true === $this->config->get('graphql.tracing.field_tracing')) {
            return $this->cachedHasAnyFieldTracing = true;
        }

        // Slow path: check per-schema overrides
        /** @var array<string, mixed> $schemas */
        $schemas = $this->config->get('graphql.schemas', []);

        foreach (array_keys($schemas) as $schemaName) {
            $config = $this->resolveConfig((string) $schemaName);

            if (null !== $config && true === ($config['field_tracing'] ?? false)) {
                return $this->cachedHasAnyFieldTracing = true;
            }
        }

        return $this->cachedHasAnyFieldTracing = false;
    }

    /**
     * Extract the per-schema `tracing` config value, handling both
     * array-based and class-based (ConfigConvertible) schema definitions.
     *
     * @return array<string, mixed>|false|null
     */
    private function resolveSchemaTracing(string $schemaName): array|false|null
    {
        /** @var mixed $schemaConfig */
        $schemaConfig = $this->config->get("graphql.schemas.$schemaName");

        if (\is_string($schemaConfig)) {
            // Class-based schema — resolve the class and call toConfig()
            try {
                /** @var object $instance */
                $instance = $this->app->make($schemaConfig);
            } catch (BindingResolutionException) {
                // Class cannot be resolved (e.g. invalid class name) — skip gracefully
                return null;
            }

            if (!$instance instanceof ConfigConvertible) {
                return null;
            }

            $converted = $instance->toConfig();

            /** @var array<string, mixed>|false|null */
            return $converted['tracing'] ?? null;
        }

        /** @var array<string, mixed>|false|null */
        return $this->config->get("graphql.schemas.$schemaName.tracing");
    }
}
