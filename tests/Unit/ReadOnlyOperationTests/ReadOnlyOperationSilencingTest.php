<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ReadOnlyOperationTests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\AutomaticPersistedQueriesMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ReadOnlyOperationMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ValidateOperationParamsMiddleware;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies that rejections raised by `ReadOnlyOperationMiddleware` are
 * silenced in `GraphQL::handleErrors()` and never reach Laravel's
 * `ExceptionHandler::report()`. They are expected client errors, analogous
 * to `ValidationError` and `AuthorizationError`, and should not produce
 * server log noise.
 */
class ReadOnlyOperationSilencingTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.method', ['GET', 'POST']);
        $app['config']->set('graphql.schemas.default.mutation', [
            'simpleEcho' => SimpleEchoMutation::class,
        ]);
        $app['config']->set('graphql.execution_middleware', [
            ValidateOperationParamsMiddleware::class,
            AutomaticPersistedQueriesMiddleware::class,
            ReadOnlyOperationMiddleware::class,
            AddAuthUserContextValueMiddleware::class,
        ]);
    }

    public function testRejectionIsNotReportedToExceptionHandler(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => 'mutation { simpleEcho(value: "value") }',
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertSame(
            'GET supports only query operation',
            $content['errors'][0]['message'],
        );
    }

    protected function resolveApplicationExceptionHandler($app): void
    {
        $handlerMock = Mockery::mock(ExceptionHandler::class);
        $handlerMock
            ->shouldReceive('report')
            ->never();

        $app->instance(ExceptionHandler::class, $handlerMock);
    }
}
