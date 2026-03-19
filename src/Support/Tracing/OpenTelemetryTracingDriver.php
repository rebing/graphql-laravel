<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ResolveInfo;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use RuntimeException;

/**
 * OpenTelemetry tracing driver.
 *
 * Emits spans following the OpenTelemetry GraphQL semantic conventions:
 * https://opentelemetry.io/docs/specs/semconv/graphql/graphql-spans/
 *
 * Requires `open-telemetry/api` (^1.0) to be installed.
 * Without an SDK configured, all spans are no-ops automatically.
 *
 * Attributes set on the operation span (standard semconv):
 *  - graphql.operation.name
 *  - graphql.operation.type
 *  - graphql.document (opt-in via config)
 *
 * Custom attributes beyond the semconv:
 *  - graphql.schema.name (operation span)
 *  - graphql.field.name  (field span, when field_tracing is enabled)
 *  - graphql.field.type  (field span, when field_tracing is enabled)
 *
 * Field spans are emitted as direct children of the operation span (flat
 * structure) regardless of resolver nesting depth.
 */
class OpenTelemetryTracingDriver implements TracingDriver
{
    private TracerInterface $tracer;

    private bool $includeDocument;

    /**
     * @param array<string, mixed> $driverOptions Merged per-schema driver options
     */
    public function __construct(array $driverOptions = [])
    {
        if (!interface_exists(TracerInterface::class)) {
            throw new RuntimeException(
                'The OpenTelemetry tracing driver requires the "open-telemetry/api" package. '
                . 'Install it with: composer require open-telemetry/api',
            );
        }

        $this->tracer = Globals::tracerProvider()->getTracer(
            'rebing/graphql-laravel',
        );
        $this->includeDocument = (bool) ($driverOptions['include_document'] ?? false);
    }

    public function startOperation(string $schemaName, ?string $operationName, ?string $operationType, ?string $source): mixed
    {
        $spanName = (null !== $operationType && '' !== $operationType)
            ? $operationType
            : 'GraphQL Operation';

        $span = $this->tracer->spanBuilder($spanName)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        $scope = $span->activate();

        $span->setAttribute('graphql.schema.name', $schemaName);

        if (null !== $operationName) {
            $span->setAttribute('graphql.operation.name', $operationName);
        }

        if (null !== $operationType) {
            $span->setAttribute('graphql.operation.type', $operationType);
        }

        if ($this->includeDocument && null !== $source) {
            $span->setAttribute('graphql.document', $source);
        }

        return new OpenTelemetryContext($span, $scope);
    }

    public function endOperation(mixed $context, ExecutionResult $result): ExecutionResult
    {
        if (!$context instanceof OpenTelemetryContext) {
            return $result;
        }

        if ([] !== $result->errors) {
            $firstError = $result->errors[0];
            $context->span->setStatus(StatusCode::STATUS_ERROR, $firstError->getMessage());

            $errorType = null !== $firstError->getPrevious()
                ? $firstError->getPrevious()::class
                : $firstError::class;
            $context->span->setAttribute('error.type', $errorType);
        }

        $context->scope->detach();
        $context->span->end();

        return $result;
    }

    public function startFieldResolve(ResolveInfo $info): mixed
    {
        $spanName = \sprintf('%s.%s', $info->parentType->name(), $info->fieldName);

        $span = $this->tracer->spanBuilder($spanName)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        $span->setAttribute('graphql.field.name', $info->fieldName);
        $span->setAttribute('graphql.field.type', (string) $info->returnType);

        return $span;
    }

    public function endFieldResolve(mixed $context, ResolveInfo $info): void
    {
        if ($context instanceof SpanInterface) {
            $context->end();
        }
    }
}
