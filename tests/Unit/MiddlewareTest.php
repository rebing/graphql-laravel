<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    public function testMiddleware(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 0,
        ]);

        self::assertObjectHasAttribute('data', $result);

        self::assertEquals($result->data, [
            'examplesMiddleware' => [$this->data[0]],
        ]);
    }

    public function testMiddlewareCanMutateArgs(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 4, // there is no index 4
        ]);

        self::assertObjectHasAttribute('data', $result);

        self::assertEquals($result->data, [
            'examplesMiddleware' => [$this->data[0]], // switched to index 0 in middleware
        ]);
    }

    public function testMiddlewareCanMutateFields(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 1,
        ]);

        self::assertObjectHasAttribute('data', $result);

        self::assertEquals(
            [
                'examplesMiddleware' => [['test' => 'ExampleMiddleware changed me!']],
            ],
            $result->data
        );
    }

    public function testMiddlewareCanThrowExceptionsBeforeResolution(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 5,
        ]);

        self::assertObjectHasAttribute('errors', $result);
        self::assertSame('Index 5 is not allowed', $result->errors[0]->getMessage());
    }

    public function testMiddlewareCanThrowExceptionsAfterResolution(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 2,
        ]);

        self::assertObjectHasAttribute('errors', $result);
        self::assertSame('Example 3 is not allowed', $result->errors[0]->getMessage());
    }

    public function testMiddlewareTerminateHappensAfterResponseIsSent(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 6,
        ]);

        self::assertObjectHasAttribute('errors', $result);
        self::assertMatchesRegularExpression('/^Undefined .* 6$/', $result->errors[0]->getMessage());
    }
}
