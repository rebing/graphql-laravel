<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

class InputTypeTest extends TestCase
{
    /**
     * Test to type
     *
     * @test
     */
    public function testToType()
    {
        $type = new ExampleInputType();
        $objectType = $type->toType();

        $this->assertInstanceOf(InputObjectType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
