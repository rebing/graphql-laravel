<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class DirectiveTest extends TestCase
{
    public function testInternalDirective(): void
    {
        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithInternalDirective']),
            'variables' => [
                'withField' => false,
            ],
        ])->assertOk()->json();

        self::assertArrayHasKey('data', $content);

        // Assert response doesn't contain the `test_validation` field
        self::assertSame([
            'examples' => array_map(function ($n) {
                return ['test' => $n['test']];
            }, $this->data),
        ], $content['data']);

        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithInternalDirective']),
            'variables' => [
                'withField' => true,
            ],
        ])->assertOk()->json();

        // Assert response does contain the `test_validation` field
        self::assertSame([
            'examples' => array_map(function ($n) {
                return ['test' => $n['test'], 'test_validation' => ['test']];
            }, $this->data),
        ], $content['data']);
    }

    public function testFieldDirective(): void
    {
        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithFieldDirective']),
        ])->assertOk()->json();

        self::assertArrayHasKey('data', $content);

        self::assertEquals([
            'examples' => array_map(function ($n) {
                return [
                    'test' => strtoupper($n['test']),
                    'alias' => strtolower($n['test']),
                ];
            }, $this->data),
        ], $content['data']);
    }

    public function testFragmentAndFieldDirective(): void
    {
        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithFragmentAndFieldDirective']),
        ])->assertOk()->json();

        self::assertArrayHasKey('data', $content);

        self::assertEquals([
            'examples' => array_map(function ($n) {
                return [
                    'test' => strtoupper($n['test']),
                    'second' => $n['test'],
                ];
            }, $this->data),
        ], $content['data']);
    }

    public function testFieldDirectiveWithArgument(): void
    {
        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithFieldDirectiveWithArgument']),
        ])->assertOk()->json();

        self::assertArrayHasKey('data', $content);

        self::assertEquals([
            'examples' => array_map(function ($n) {
                return [
                    'test' => trim($n['test'], 'E'),
                ];
            }, $this->data),
        ], $content['data']);
    }

    public function testConsecutiveFieldDirectives(): void
    {
        $content = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithConsecutiveFieldDirectives']),
        ])->assertOk()->json();

        self::assertArrayHasKey('data', $content);

        self::assertEquals([
            'examples' => array_map(function ($n) {
                return ['test' => strtolower(strtoupper($n['test']))];
            }, $this->data),
        ], $content['data']);
    }
}
