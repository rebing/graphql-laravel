<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

class ExecutionMiddlewareTest extends TestCase
{
    public function testMiddlewareCanReturnResponse(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            CacheMiddleware::class,
        ]);

        $result = GraphQL::query($this->queries['examplesWithVariables'], [
            'index' => 1,
        ]);

        self::assertArrayHasKey('data', $result);

        self::assertEquals($result['data'], [
            'examples' => [['test' => 'Cached response']],
        ]);
    }

    public function testMiddlewareCanMutateArgs(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ChangeVariableMiddleware::class,
        ]);

        $result = GraphQL::query($this->queries['examplesWithVariables'], [
            'index' => '1',
        ]);

        self::assertArrayHasKey('data', $result);
        self::assertEquals($result['data'], [
            'examples' => [['test' => 'Example 2']],
        ]);
    }

    public function testMiddlewareCanMutateQueryAndSendParsedQueryAlong(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ChangeQueryArgTypeMiddleware::class,
        ]);

        $result = GraphQL::query($this->queries['examplesWithWrongTypeOfArgument'], [
            'indexVariable' => 1,
        ]);

        self::assertArrayHasKey('data', $result);

        self::assertEquals(
            [
                'examples' => [['test' => 'Example 2']],
            ],
            $result['data']
        );
    }
}
