<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

/**
 * A class-based schema reference used by `TracingManagerTest` to verify that
 * a schema configured as a class string but NOT implementing
 * `Rebing\GraphQL\Support\Contracts\ConfigConvertible` is gracefully skipped
 * when resolving per-schema tracing config.
 */
class NotConfigConvertibleSchema
{
}
