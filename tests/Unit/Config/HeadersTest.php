<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Config;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;

class HeadersTest extends TestCase
{
    public function testCustomHeaders(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        $this->assertTrue($response->headers->has('x-custom'));
        $this->assertSame('Header Value', $response->headers->get('x-custom'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql', [
            'headers' => [
                'X-Custom' => 'Header Value',
            ],

            'schemas' => [
                'default' => [
                    'query' => [
                        'examples' => ExamplesQuery::class,
                    ],
                ],
            ],

            'types' => [
                'Example' => ExampleType::class,
            ],
        ]);
    }
}
