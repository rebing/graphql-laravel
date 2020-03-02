<?php

declare(strict_types=1);

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

        $this->assertObjectHasAttribute('data', $result);

        $this->assertEquals($result->data, [
            'examplesMiddleware' => [$this->data[0]],
        ]);
    }

    public function testMiddlewareCanMutateArgs(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 4, // there is no index 4
        ]);

        $this->assertObjectHasAttribute('data', $result);

        $this->assertEquals($result->data, [
            'examplesMiddleware' => [$this->data[0]], // switched to index 0 in middleware
        ]);
    }

    public function testMiddlewareCanMutateFields(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 1,
        ]);

        $this->assertObjectHasAttribute('data', $result);

        $this->assertEquals($result->data, [
            'examplesMiddleware' => [['test' => 'ExampleMiddleware changed me!']],
        ]);
    }

    public function testMiddlewareCanThrowExceptionsBeforeResolution(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 5,
        ]);

        $this->assertObjectHasAttribute('errors', $result);
        $this->assertSame('Index 5 is not allowed', $result->errors[0]->message);
    }

    public function testMiddlewareCanThrowExceptionsAfterResolution(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 2,
        ]);

        $this->assertObjectHasAttribute('errors', $result);
        $this->assertSame('Example 3 is not allowed', $result->errors[0]->message);
    }

    public function testMiddlewareTerimateHappensAfterResponseIsSent(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['exampleMiddleware'], [
            'index' => 6,
        ]);

        $this->assertObjectHasAttribute('errors', $result);
        $this->assertSame('Undefined offset: 6', $result->errors[0]->message);
    }
}
