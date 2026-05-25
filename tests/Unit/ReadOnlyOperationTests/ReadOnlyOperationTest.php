<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ReadOnlyOperationTests;

use Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\AutomaticPersistedQueriesMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ReadOnlyOperationMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ValidateOperationParamsMiddleware;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Behaviour tests for `ReadOnlyOperationMiddleware`.
 *
 * The default schema is reconfigured to accept `GET` so we can exercise the
 * read-only enforcement path; without that, GET requests would never reach
 * the controller.
 */
class ReadOnlyOperationTest extends TestCase
{
    private const MUTATION = 'mutation { simpleEcho(value: "value") }';

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.method', ['GET', 'POST']);
        $app['config']->set('graphql.schemas.default.mutation', [
            'simpleEcho' => SimpleEchoMutation::class,
        ]);
    }

    public function testWithoutMiddlewareGetMutationStillExecutes(): void
    {
        // Sanity check: documents the gap the middleware closes. Without it
        // in the pipeline a GET-submitted mutation runs end-to-end.
        $response = $this->call('GET', '/graphql', [
            'query' => self::MUTATION,
        ]);

        self::assertEquals(200, $response->getStatusCode());
        self::assertSame(
            ['data' => ['simpleEcho' => 'value']],
            $response->getData(true),
        );
    }

    public function testGetQueryPassesWithMiddlewareEnabled(): void
    {
        $this->enableReadOnlyMiddleware();

        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testGetMutationIsRejected(): void
    {
        $this->enableReadOnlyMiddleware();

        $response = $this->call('GET', '/graphql', [
            'query' => self::MUTATION,
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        unset($content['errors'][0]['extensions']);

        self::assertEquals(
            ['errors' => [['message' => 'GET supports only query operation']]],
            $content,
        );
    }

    public function testGetSubscriptionIsRejected(): void
    {
        $this->enableReadOnlyMiddleware();

        // The schema has no `Subscription` type registered, but the parser
        // accepts the syntax and the middleware rejects on operation type
        // alone — independent of execution.
        $response = $this->call('GET', '/graphql', [
            'query' => 'subscription { examples { test } }',
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        unset($content['errors'][0]['extensions']);

        self::assertEquals(
            ['errors' => [['message' => 'GET supports only query operation']]],
            $content,
        );
    }

    public function testPostMutationStillPasses(): void
    {
        $this->enableReadOnlyMiddleware();

        $response = $this->call('POST', '/graphql', [
            'query' => self::MUTATION,
        ]);

        self::assertEquals(200, $response->getStatusCode());
        self::assertSame(
            ['data' => ['simpleEcho' => 'value']],
            $response->getData(true),
        );
    }

    public function testGetMultiOperationSelectingQueryPasses(): void
    {
        $this->enableReadOnlyMiddleware();

        $document = <<<'GRAPHQL'
query Q { examples { test } }
mutation M { simpleEcho(value: "value") }
GRAPHQL;

        $response = $this->call('GET', '/graphql', [
            'query' => $document,
            'operationName' => 'Q',
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testGetMultiOperationSelectingMutationIsRejected(): void
    {
        $this->enableReadOnlyMiddleware();

        $document = <<<'GRAPHQL'
query Q { examples { test } }
mutation M { simpleEcho(value: "value") }
GRAPHQL;

        $response = $this->call('GET', '/graphql', [
            'query' => $document,
            'operationName' => 'M',
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        unset($content['errors'][0]['extensions']);

        self::assertEquals(
            ['errors' => [['message' => 'GET supports only query operation']]],
            $content,
        );
    }

    public function testGetMultiOperationWithoutOperationNameDeferToWebonyx(): void
    {
        // When `operationName` is omitted on a multi-operation document
        // `AST::getOperationAST()` returns null and we deliberately fall
        // through to webonyx, which produces its own
        // `FailedToDetermineOperationType` error rather than our message.
        $this->enableReadOnlyMiddleware();

        $document = <<<'GRAPHQL'
query Q { examples { test } }
mutation M { simpleEcho(value: "value") }
GRAPHQL;

        $response = $this->call('GET', '/graphql', [
            'query' => $document,
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('errors', $content);
        self::assertNotEquals(
            'GET supports only query operation',
            $content['errors'][0]['message'],
        );
    }

    private function enableReadOnlyMiddleware(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ValidateOperationParamsMiddleware::class,
            AutomaticPersistedQueriesMiddleware::class,
            ReadOnlyOperationMiddleware::class,
            AddAuthUserContextValueMiddleware::class,
        ]);
    }
}
