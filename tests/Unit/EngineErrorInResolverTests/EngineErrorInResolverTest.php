<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\EngineErrorInResolverTests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Rebing\GraphQL\Tests\TestCase;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class EngineErrorInResolverTest extends TestCase
{
    public function testForEngineError(): void
    {
        $result = $this->graphql('query { queryWithEngineErrorInCode }', [
            'expectErrors' => true,
        ]);

        // Using a regex here because in some cases the message gets prefixed with "Type error:"
        $this->assertRegExp('/Simulating a TypeError/', $result['errors'][0]['debugMessage']);
    }

    protected function resolveApplicationExceptionHandler($app)
    {
        // We expect the error in QueryWithEngineErrorInCodeQuery  to trigger
        // reporting to the handler (as opposed to swallowing it silently).
        $handlerMock = Mockery::mock(ExceptionHandler::class);
        $handlerMock
            ->shouldReceive('report')
            ->with(Mockery::on(
                function (FatalThrowableError $error) {
                    // Using a regex here because in some cases the message gets prefixed with "Type error:"
                    $this->assertRegExp('/Simulating a TypeError/', $error->getMessage());

                    return true;
                }
            ))
            ->once();

        $app->instance(ExceptionHandler::class, $handlerMock);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                QueryWithEngineErrorInCodeQuery::class,
            ],
        ]);
    }
}
