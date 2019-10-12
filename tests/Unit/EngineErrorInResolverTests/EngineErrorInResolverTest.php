<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\EngineErrorInResolverTests;

use Mockery;
use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class EngineErrorInResolverTest extends TestCase
{
    private const ERROR_REGEX = '/QueryWithEngineErrorInCodeQuery::getResult\(\) must be of the type int\w*, string given, called in/';

    public function testForEngineError(): void
    {
        $result = $this->graphql('query { queryWithEngineErrorInCode }', [
            'expectErrors' => true,
        ]);

        $this->assertRegExp(static::ERROR_REGEX, $result['errors'][0]['debugMessage']);
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
                    $this->assertRegExp(static::ERROR_REGEX, $error->getMessage());

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
