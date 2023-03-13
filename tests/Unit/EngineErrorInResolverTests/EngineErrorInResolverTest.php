<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\EngineErrorInResolverTests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Rebing\GraphQL\Tests\TestCase;
use Throwable;

class EngineErrorInResolverTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                QueryWithEngineErrorInCodeQuery::class,
            ],
        ]);
    }

    public function testForEngineError(): void
    {
        $result = $this->httpGraphql('query { queryWithEngineErrorInCode }', [
            'expectErrors' => true,
        ]);

        // Using a regex here because in some cases the message gets prefixed with "Type error:"
        self::assertMatchesRegularExpression('/Simulating a TypeError/', $result['errors'][0]['extensions']['debugMessage']);
    }

    protected function resolveApplicationExceptionHandler($app): void
    {
        // We expect the error in QueryWithEngineErrorInCodeQuery  to trigger
        // reporting to the handler (as opposed to swallowing it silently).
        $handlerMock = Mockery::mock(ExceptionHandler::class);
        $handlerMock
            ->shouldReceive('report')
            ->with(Mockery::on(
                function (Throwable $error) {
                    // Using a regex here because in some cases the message gets prefixed with "Type error:"
                    $this->assertMatchesRegularExpression('/Simulating a TypeError/', $error->getMessage());

                    return true;
                }
            ))
            ->once();

        $app->instance(ExceptionHandler::class, $handlerMock);
    }
}
