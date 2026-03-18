<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\OperationParams;
use Rebing\GraphQL\Support\Tracing\TracingManager;
use Rebing\GraphQL\Support\Tracing\TracingOperationScope;
use Throwable;

/**
 * Execution middleware that instruments GraphQL operations via a TracingDriver.
 *
 * This middleware wraps the entire execution pipeline:
 *  - Calls TracingDriver::startOperation() before execution
 *  - Calls TracingDriver::endOperation() after execution
 *
 * When field tracing is enabled for the current schema, the user's
 * context value is wrapped in a {@see TracingOperationScope} so that
 * {@see \Rebing\GraphQL\Support\Tracing\TracingResolverMiddleware} can
 * access the driver without relying on shared mutable state.
 *
 * It is automatically prepended to the execution middleware list when
 * a tracing driver is configured (globally or per-schema).
 */
class TracingExecutionMiddleware extends AbstractExecutionMiddleware
{
    public function __construct(
        private readonly TracingManager $tracingManager,
    ) {
    }

    public function handle(
        string $schemaName,
        Schema $schema,
        OperationParams $params,
        $rootValue,
        $contextValue,
        Closure $next,
    ): ExecutionResult {
        $driver = $this->tracingManager->driverFor($schemaName);

        if (null === $driver) {
            return $next($schemaName, $schema, $params, $rootValue, $contextValue);
        }

        // When field tracing is enabled, wrap the context so the resolver
        // middleware can access the driver without shared mutable state.
        $fieldTracing = $this->tracingManager->fieldTracingEnabledFor($schemaName);
        $effectiveContext = $fieldTracing
            ? new TracingOperationScope($contextValue, $driver)
            : $contextValue;

        $operationType = $this->resolveOperationType($params);

        $traceContext = $driver->startOperation(
            $schemaName,
            $params->operation,
            $operationType,
            $params->query,
        );

        try {
            /** @var ExecutionResult $result */
            $result = $next($schemaName, $schema, $params, $rootValue, $effectiveContext);
        } catch (Throwable $e) {
            $driver->endOperation($traceContext, new ExecutionResult(null, [new Error($e->getMessage(), previous: $e)]));

            throw $e;
        }

        return $driver->endOperation($traceContext, $result);
    }

    /**
     * Resolve the operation type (query/mutation/subscription) from the parsed query.
     */
    private function resolveOperationType(OperationParams $params): ?string
    {
        try {
            $document = $params->getParsedQuery();
        } catch (Throwable) {
            return null;
        }

        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                // If an operation name is specified, match it
                if (null !== $params->operation && $definition->name?->value !== $params->operation) {
                    continue;
                }

                return $definition->operation;
            }
        }

        return null;
    }
}
