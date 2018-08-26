<?php

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;

class EndpointTest extends TestCase
{
    /**
     * Test get with default schema
     *
     * @test
     */
    public function testGetDefault()
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples']
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals($content['data'], [
            'examples' => $this->data
        ]);
    }

    /**
     * Test get with custom schema
     *
     * @test
     */
    public function testGetCustom()
    {
        $response = $this->call('GET', '/graphql/custom', [
            'query' => $this->queries['examplesCustom']
        ]);

        $content = $response->getData(true);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals($content['data'], [
            'examplesCustom' => $this->data
        ]);
    }

    /**
     * Test get with variables
     *
     * @test
     */
    public function testGetWithVariables()
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'params' => [
                'index' => 0
            ]
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals($content['data'], [
            'examples' => [
                $this->data[0]
            ]
        ]);
    }

    /**
     * Test get with unauthorized query
     *
     * @test
     */
    public function testGetUnauthorized()
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithAuthorize']
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('errors', $content);
        $this->assertEquals($content['errors'][0]['message'], 'Unauthorized');
        $this->assertNull($content['data']['examplesAuthorize']);
    }

    /**
     * Test support batched queries
     *
     * @test
     */
    public function testBatchedQueries() {
        $response = $this->call('GET', '/graphql', [
            [
                'query' => $this->queries['examplesWithVariables'],
                'params' => [
                    'index' => 0
                ]
            ],
            [
                'query' => $this->queries['examplesWithVariables'],
                'params' => [
                    'index' => 0
                ]
            ]
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        $this->assertArrayHasKey(0, $content);
        $this->assertArrayHasKey(1, $content);
        $this->assertEquals($content[0]['data'], [
            'examples' => [
                $this->data[0]
            ]
        ]);
        $this->assertEquals($content[1]['data'], [
            'examples' => [
                $this->data[0]
            ]
        ]);
    }
}
