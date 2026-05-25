<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TerminableMiddlewareTest;

use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies that a resolver middleware's `terminate()` method is invoked via
 * Laravel's `app()->terminating()` lifecycle after the HTTP response is sent.
 *
 * The README documents this as "Terminable middleware" but no existing test
 * confirms `terminate()` actually runs (existing tests verify only that the
 * presence of a terminate-throwing middleware doesn't break the response).
 */
class TerminableMiddlewareTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                TerminableExampleQuery::class,
            ],
        ]);

        TerminableMiddleware::$handleCalls = 0;
        TerminableMiddleware::$terminateCalls = 0;
        TerminableMiddleware::$terminateArguments = [];
    }

    public function testTerminateMethodIsCalledAfterRequest(): void
    {
        $result = $this->httpGraphql('{ terminableExample(value: "hello") }');

        self::assertSame(['data' => ['terminableExample' => 'resolved-hello']], $result);

        // handle() must have been invoked exactly once.
        self::assertSame(1, TerminableMiddleware::$handleCalls);

        // terminate() must have been invoked exactly once with the args & result.
        self::assertSame(1, TerminableMiddleware::$terminateCalls);
        self::assertCount(1, TerminableMiddleware::$terminateArguments);

        $args = TerminableMiddleware::$terminateArguments[0];
        self::assertSame(['value' => 'hello'], $args['args']);
        self::assertSame('resolved-hello', $args['result']);
    }
}
