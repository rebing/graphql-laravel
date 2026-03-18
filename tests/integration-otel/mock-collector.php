<?php

declare(strict_types = 1);

/**
 * Minimal mock OTLP HTTP collector.
 *
 * Accepts POST requests to /v1/traces and appends the JSON body to a file.
 * Returns an empty ExportTraceServiceResponse ({}) so the OTel SDK treats
 * the export as successful.
 *
 * Usage:
 *   TRACE_FILE=/path/to/traces.json php -S 127.0.0.1:4318 mock-collector.php
 */
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';

if ('POST' === $method && str_contains($uri, '/v1/traces')) {
    $body = file_get_contents('php://input');
    $traceFile = getenv('TRACE_FILE');

    if (!$traceFile) {
        http_response_code(500);
        echo 'TRACE_FILE env var not set';

        return;
    }

    file_put_contents($traceFile, $body . "\n", FILE_APPEND);

    header('Content-Type: application/json');
    echo '{}';
} else {
    http_response_code(404);
}
