<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Config;

use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;

class JsonEncodingOptionsTest extends TestCase
{
    public function testCustomHeaders(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        $json = <<<'JSON'
{
    "data": {
        "examples": [
            {
                "test": "Example 1"
            },
            {
                "test": "Example 2"
            },
            {
                "test": "Example 3"
            }
        ]
    }
}
JSON;
        $this->assertSame($json, $response->content());
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql', [
            'json_encoding_options' => JSON_PRETTY_PRINT,

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
