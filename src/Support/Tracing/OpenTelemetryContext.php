<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Tracing;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;

/**
 * @internal Context object passed between startOperation() and endOperation()
 */
readonly class OpenTelemetryContext
{
    public function __construct(
        public SpanInterface $span,
        public ScopeInterface $scope,
    ) {
    }
}
