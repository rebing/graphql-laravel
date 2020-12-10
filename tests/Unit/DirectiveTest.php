<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class DirectiveTest extends TestCase
{
    /**
     * Test simple directive.
     */
    public function testSimpleDirective(): void
    {
        config(['graphql.defaultFieldResolver' => [\Rebing\GraphQL\Helpers::class, 'defaultFieldResolverWithDirectives']]);

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithDirective']),
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey('data', $content);

        $this->assertEquals([
            'examples' => array_map(function ($n) {
                return ['test' => strtoupper($n['test'])];
            }, $this->data),
        ], $content['data']);
    }

    /**
     * Test simple directive.
     */
    public function testConsecutiveDirectives(): void
    {
        config(['graphql.defaultFieldResolver' => [\Rebing\GraphQL\Helpers::class, 'defaultFieldResolverWithDirectives']]);

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examplesWithConsecutiveDirectives']),
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey('data', $content);

        $this->assertEquals([
            'examples' => array_map(function ($n) {
                return ['test' => strtolower(strtoupper($n['test']))];
            }, $this->data),
        ], $content['data']);
    }
}
