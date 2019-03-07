<?php

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Error\Error;
use Rebing\GraphQL\Error\ValidationError;

class GraphQLQueryTest extends TestCase
{
    /**
     * Test query
     *
     * @test
     */
    public function testQueryAndReturnResult()
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        $this->assertObjectHasAttribute('data', $result);

        $this->assertEquals($result->data, [
            'examples' => $this->data
        ]);
    }

    /**
     * Test query methods
     *
     * @test
     */
    public function testQuery()
    {
        $resultArray = GraphQL::query($this->queries['examples']);
        $result = GraphQL::queryAndReturnResult($this->queries['examples']);

        $this->assertInternalType('array', $resultArray);
        $this->assertArrayHasKey('data', $resultArray);
        $this->assertEquals($resultArray['data'], $result->data);
    }

    /**
     * Test query with variables
     *
     * @test
     */
    public function testQueryAndReturnResultWithVariables()
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithVariables'], [
            'index' => 0
        ]);

        $this->assertObjectHasAttribute('data', $result);
        $this->assertCount(0, $result->errors);
        $this->assertEquals($result->data, [
            'examples' => [
                $this->data[0]
            ]
        ]);
    }

    /**
     * Test query with complex variables.
     *
     * @test
     */
    public function testQueryAndReturnResultWithFilterVariables()
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithFilterVariables'], [
            'filter' => [
                'test' => 'Example 1'
            ]
        ]);

        $this->assertObjectHasAttribute('data', $result);
        // When XDebug is used with breaking on exceptions the real error will 
        // be visible in case the recursion for getInputTypeRules runs away.
        // GraphQL\Error\Error: Maximum function nesting level of '256' reached, aborting!
        $this->assertCount(0, $result->errors);
        $this->assertEquals($result->data, [
            'examplesFiltered' => [
                $this->data[0]
            ]
        ]);
    }

    /**
     * Test query with authorize
     *
     * @test
     */
    public function testQueryAndReturnResultWithAuthorize()
    {
        $result = GraphQL::query($this->queries['examplesWithAuthorize']);
        $this->assertNull($result['data']['examplesAuthorize']);
        $this->assertEquals('Unauthorized', $result['errors'][0]['message']);
    }

    /**
     * Test query with schema
     *
     * @test
     */
    public function testQueryAndReturnResultWithSchema()
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesCustom'], null, [
            'schema' => [
                'query' => [
                    'examplesCustom' => ExamplesQuery::class
                ]
            ]
        ]);

        $this->assertObjectHasAttribute('data', $result);
        $this->assertCount(0, $result->errors);
        $this->assertEquals($result->data, [
            'examplesCustom' => $this->data
        ]);
    }

    /**
     * Test query with error
     *
     * If an error was encountered before execution begins, the data entry should not be present in the result.
     *
     * @test
     */
    public function testQueryWithError()
    {
        $result = GraphQL::query($this->queries['examplesWithError']);

        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(1, $result['errors']);
        $this->assertArrayHasKey('message', $result['errors'][0]);
        $this->assertArrayHasKey('locations', $result['errors'][0]);
    }

    /**
     * Test query with validation error
     *
     * @test
     */
    public function testQueryWithValidationError()
    {
        $result = GraphQL::query($this->queries['examplesWithValidation']);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('validation', $result['errors'][0]);
        $this->assertTrue($result['errors'][0]['validation']->has('index'));
    }

    /**
     * Test query with validation without error
     *
     * @test
     */
    public function testQueryWithValidation()
    {
        $result = GraphQL::query($this->queries['examplesWithValidation'], [
            'index' => 0
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayNotHasKey('errors', $result);
    }
}
