#!/usr/bin/env bash

# End-to-end test for the OpenTelemetry tracing integration.
#
# Creates a fresh Laravel installation, installs our package together with the
# OpenTelemetry SDK + OTLP exporter, runs a mock OTLP collector, fires a
# GraphQL request and asserts that the collector received the expected spans.
#
# This script is meant to be run on CI environments.

set -euo pipefail

LARAVEL_VERSION="${1:-}"
if [[ "$LARAVEL_VERSION" = "" ]]; then
    echo "ERROR: Usage of this script is: $0 <laravel version>"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# --------------------------------------------------------------------------- #
#  Cleanup helper – kill background processes on exit
# --------------------------------------------------------------------------- #
PIDS_TO_KILL=()
cleanup() {
    for pid in "${PIDS_TO_KILL[@]+"${PIDS_TO_KILL[@]}"}"; do
        kill "$pid" 2>/dev/null || true
    done
}
trap cleanup EXIT

# --------------------------------------------------------------------------- #
#  1. Create a fresh Laravel application
# --------------------------------------------------------------------------- #
echo "Install Laravel"
composer create-project --prefer-dist "laravel/laravel:$LARAVEL_VERSION" ../laravel-otel || exit 1
cd ../laravel-otel

echo "Make sure the minimum stability is dev to allow bringing in our local project"
tmp=$(mktemp)
jq '.["minimum-stability"] = "dev"' composer.json > "$tmp" && mv "$tmp" composer.json
rm -f "$tmp"

# --------------------------------------------------------------------------- #
#  2. Install our package from the local path
# --------------------------------------------------------------------------- #
echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../graphql-laravel" } ],|' -i composer.json || exit 1
composer require --dev "rebing/graphql-laravel:*" || exit 1

# --------------------------------------------------------------------------- #
#  3. Install OpenTelemetry packages
# --------------------------------------------------------------------------- #
echo "Allow composer plugins required by OTel packages"
composer config --no-plugins allow-plugins.php-http/discovery true
composer config --no-plugins allow-plugins.tbachert/spi true

echo "Install OpenTelemetry SDK + OTLP exporter"
# google/protobuf is pulled in transitively via open-telemetry/gen-otlp-protobuf
composer require \
    open-telemetry/api \
    open-telemetry/sdk \
    open-telemetry/exporter-otlp \
    || exit 1

# --------------------------------------------------------------------------- #
#  4. Publish config, create ExampleQuery, enable tracing
# --------------------------------------------------------------------------- #
echo "Publish vendor files"
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider" || exit 1

echo "Make GraphQL ExampleQuery"
php artisan make:graphql:query ExampleQuery || exit 1

echo "Add ExampleQuery to config"
sed -e "s|// ExampleQuery::class,|\\\App\\\GraphQL\\\Queries\\\ExampleQuery::class,|" -i config/graphql.php || exit 1

echo "Enable OpenTelemetry tracing driver"
sed -e "s|'driver' => null,|'driver' => \\\Rebing\\\GraphQL\\\Support\\\Tracing\\\OpenTelemetryTracingDriver::class,|" -i config/graphql.php || exit 1

# --------------------------------------------------------------------------- #
#  5. Start mock OTLP collector
# --------------------------------------------------------------------------- #
TRACE_FILE="$(pwd)/storage/otel-traces.jsonl"
rm -f "$TRACE_FILE"

echo "Start mock OTLP collector on port 4318"
TRACE_FILE="$TRACE_FILE" php -S 127.0.0.1:4318 "$SCRIPT_DIR/mock-collector.php" >/dev/null 2>&1 &
PIDS_TO_KILL+=($!)
sleep 1

# --------------------------------------------------------------------------- #
#  6. Start Laravel web server with OTel env vars
# --------------------------------------------------------------------------- #
echo "Start web server on port 8001 (with OpenTelemetry auto-configuration)"
OTEL_PHP_AUTOLOAD_ENABLED=true \
OTEL_TRACES_EXPORTER=otlp \
OTEL_EXPORTER_OTLP_PROTOCOL=http/json \
OTEL_EXPORTER_OTLP_ENDPOINT=http://127.0.0.1:4318 \
OTEL_PHP_TRACES_PROCESSOR=simple \
php -S 127.0.0.1:8001 -t public >/dev/null 2>&1 &
PIDS_TO_KILL+=($!)
sleep 2

# --------------------------------------------------------------------------- #
#  7. Send a GraphQL request
# --------------------------------------------------------------------------- #
echo "Send GraphQL HTTP request to fetch ExampleQuery"
RESPONSE=$(curl 'http://127.0.0.1:8001/graphql' -sSfL -X POST \
    -H 'Content-Type: application/json' \
    -d '{"query":"{example}"}' 2>&1) || true

if ! echo "$RESPONSE" | grep -q 'The example works'; then
    echo "Example GraphQL query DID NOT work 🚨"
    echo "Response: $RESPONSE"
    cat storage/logs/* 2>/dev/null || true
    exit 1
fi
echo "Example GraphQL query works 👍"

# --------------------------------------------------------------------------- #
#  8. Verify OTLP traces were captured
# --------------------------------------------------------------------------- #

# Small pause – SimpleSpanProcessor exports synchronously during the request,
# so the file should already exist, but give the filesystem a moment.
sleep 1

if [[ ! -s "$TRACE_FILE" ]]; then
    echo "Trace file is missing or empty 🚨"
    echo "Expected: $TRACE_FILE"
    exit 1
fi

echo ""
echo "=== Verifying captured OTLP trace data ==="

# The file may contain multiple JSON lines (one per export call).
# We read only the first line for verification.
TRACE_JSON=$(head -n1 "$TRACE_FILE")

# --- Verify a span named "query" exists ---
if ! echo "$TRACE_JSON" | jq -e '
    .resourceSpans[].scopeSpans[].spans[]
    | select(.name == "query")
' >/dev/null 2>&1; then
    echo "FAIL: Expected a span named 'query' 🚨"
    echo "$TRACE_JSON" | jq .
    exit 1
fi
echo "  ✓ Span named 'query' found"

# --- Verify instrumentation scope ---
if ! echo "$TRACE_JSON" | jq -e '
    .resourceSpans[].scopeSpans[]
    | select(.scope.name == "rebing/graphql-laravel")
' >/dev/null 2>&1; then
    echo "FAIL: Expected instrumentation scope 'rebing/graphql-laravel' 🚨"
    echo "$TRACE_JSON" | jq .
    exit 1
fi
echo "  ✓ Instrumentation scope 'rebing/graphql-laravel'"

# --- Verify graphql.operation.type attribute ---
if ! echo "$TRACE_JSON" | jq -e '
    .resourceSpans[].scopeSpans[].spans[].attributes[]
    | select(.key == "graphql.operation.type" and .value.stringValue == "query")
' >/dev/null 2>&1; then
    echo "FAIL: Expected attribute graphql.operation.type = 'query' 🚨"
    echo "$TRACE_JSON" | jq .
    exit 1
fi
echo "  ✓ Attribute graphql.operation.type = 'query'"

# --- Verify graphql.schema.name attribute ---
if ! echo "$TRACE_JSON" | jq -e '
    .resourceSpans[].scopeSpans[].spans[].attributes[]
    | select(.key == "graphql.schema.name" and .value.stringValue == "default")
' >/dev/null 2>&1; then
    echo "FAIL: Expected attribute graphql.schema.name = 'default' 🚨"
    echo "$TRACE_JSON" | jq .
    exit 1
fi
echo "  ✓ Attribute graphql.schema.name = 'default'"

# --- Verify span kind is SERVER (value 2 in OTLP JSON) ---
if ! echo "$TRACE_JSON" | jq -e '
    .resourceSpans[].scopeSpans[].spans[]
    | select(.name == "query" and .kind == 2)
' >/dev/null 2>&1; then
    echo "FAIL: Expected span kind SERVER (2) on 'query' span 🚨"
    echo "$TRACE_JSON" | jq .
    exit 1
fi
echo "  ✓ Span kind is SERVER"

echo ""
echo "All OpenTelemetry trace assertions passed 👍"
