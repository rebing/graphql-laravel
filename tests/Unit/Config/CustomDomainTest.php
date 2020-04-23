<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Config;

use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;

class CustomDomainTest extends TestCase
{
    private $domain = 'http://exampleDomain.tld';

    public function testCustomDomain(): void
    {
        $response = $this->call('GET', '/graphiql');
        $response->assertViewHas('graphqlPath', $this->domain . '/' . config('graphql.prefix'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql', [
            'domain' => $this->domain,

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
