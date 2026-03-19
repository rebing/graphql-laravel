<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Tracing\OpenTelemetryTracingDriver;
use Rebing\GraphQL\Tests\TestCase;

class OpenTelemetryTracingDriverTest extends TestCase
{
    private ?InMemoryExporter $exporter = null;

    private ?ScopeInterface $otelScope = null;

    protected function setUp(): void
    {
        $this->exporter = new InMemoryExporter();
        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($this->exporter));

        $this->otelScope = Configurator::create()
            ->withTracerProvider($tracerProvider)
            ->activate();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->otelScope?->detach();
        $this->otelScope = null;
        $this->exporter = null;

        parent::tearDown();
    }

    /**
     * @return list<SpanDataInterface>
     */
    private function getExportedSpans(): array
    {
        self::assertNotNull($this->exporter);

        /** @var list<SpanDataInterface> */
        return $this->exporter->getSpans();
    }

    public function testOperationSpanCreatedForQuery(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);

        $span = $spans[0];
        self::assertInstanceOf(SpanDataInterface::class, $span);
        self::assertSame('query', $span->getName());
        self::assertSame(SpanKind::KIND_SERVER, $span->getKind());
        self::assertSame('query', $span->getAttributes()->get('graphql.operation.type'));
    }

    public function testOperationSpanCreatedForMutation(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult(
            'mutation UpdateExample($test: String) { updateExample(test: $test) { test } }',
            ['test' => 'Hello'],
        );

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);

        $span = $spans[0];
        self::assertSame('mutation', $span->getName());
        self::assertSame('mutation', $span->getAttributes()->get('graphql.operation.type'));
    }

    public function testOperationNameAttribute(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult(
            $this->queries['examples'],
            null,
            ['operationName' => 'QueryExamples'],
        );

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertSame('QueryExamples', $spans[0]->getAttributes()->get('graphql.operation.name'));
    }

    public function testDocumentNotIncludedByDefault(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertNull($spans[0]->getAttributes()->get('graphql.document'));
    }

    public function testDocumentIncludedWhenEnabled(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);
        $this->app['config']->set('graphql.tracing.driver_options.include_document', true);

        $query = $this->queries['examples'];
        GraphQL::queryAndReturnResult($query);

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertSame($query, $spans[0]->getAttributes()->get('graphql.document'));
    }

    public function testErrorStatusSetOnFailure(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        // Execute an invalid query to trigger an error
        GraphQL::queryAndReturnResult('{ nonExistentField }');

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertSame(StatusCode::STATUS_ERROR, $spans[0]->getStatus()->getCode());
        self::assertNotEmpty($spans[0]->getStatus()->getDescription());
        self::assertNotNull($spans[0]->getAttributes()->get('error.type'));
    }

    public function testFieldTracingCreatesChildSpans(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);
        $this->app['config']->set('graphql.tracing.field_tracing', true);

        GraphQL::queryAndReturnResult($this->queries['examplesWithVariables'], ['index' => 0]);

        $spans = $this->getExportedSpans();

        // At minimum: 1 operation span + field spans
        self::assertGreaterThan(1, \count($spans));

        // Separate operation and field spans by kind
        $serverSpans = array_filter($spans, static fn (SpanDataInterface $s): bool => SpanKind::KIND_SERVER === $s->getKind());
        $internalSpans = array_filter($spans, static fn (SpanDataInterface $s): bool => SpanKind::KIND_INTERNAL === $s->getKind());

        self::assertCount(1, $serverSpans, 'Expected exactly 1 operation (SERVER) span');
        self::assertNotEmpty($internalSpans, 'Expected at least 1 field (INTERNAL) span');

        // Verify field spans are children of the operation span
        $operationSpan = array_values($serverSpans)[0];
        $operationSpanId = $operationSpan->getSpanId();

        foreach ($internalSpans as $fieldSpan) {
            self::assertSame(
                $operationSpanId,
                $fieldSpan->getParentSpanId(),
                \sprintf('Field span "%s" should be a child of the operation span', $fieldSpan->getName()),
            );
        }

        // Each field span should have field attributes
        foreach ($internalSpans as $fieldSpan) {
            self::assertNotNull($fieldSpan->getAttributes()->get('graphql.field.name'));
            self::assertNotNull($fieldSpan->getAttributes()->get('graphql.field.type'));
        }
    }

    public function testNoFieldSpansWithoutFieldTracing(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);
        // field_tracing defaults to false, but be explicit
        $this->app['config']->set('graphql.tracing.field_tracing', false);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        $spans = $this->getExportedSpans();

        // Only the operation span, no field spans
        self::assertCount(1, $spans);
        self::assertSame(SpanKind::KIND_SERVER, $spans[0]->getKind());
    }

    public function testSuccessfulQueryHasUnsetStatus(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertSame(StatusCode::STATUS_UNSET, $spans[0]->getStatus()->getCode());
    }

    public function testSpanInstrumentationScope(): void
    {
        $this->app['config']->set('graphql.tracing.driver', OpenTelemetryTracingDriver::class);

        GraphQL::queryAndReturnResult($this->queries['examples']);

        $spans = $this->getExportedSpans();
        self::assertCount(1, $spans);
        self::assertSame('rebing/graphql-laravel', $spans[0]->getInstrumentationScope()->getName());
    }
}
