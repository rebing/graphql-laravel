<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class TypeTest extends TestCase
{
    /**
     * Test getFields
     *
     * @test
     */
    public function testGetFields()
    {
        $type = new ExampleType();
        $fields = $type->getFields();

        $this->assertArrayHasKey('test', $fields);
        $this->assertEquals($fields['test'], [
            'type' => Type::string(),
            'description' => 'A test field'
        ]);
    }

    /**
     * Test get attributes
     *
     * @test
     */
    public function testGetAttributes()
    {
        $type = new ExampleType();
        $attributes = $type->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('fields', $attributes);
        $this->assertInstanceOf(Closure::class, $attributes['fields']);
        $this->assertInternalType('array', $attributes['fields']());
    }

    /**
     * Test get attributes fields closure
     *
     * @test
     */
    public function testGetAttributesFields()
    {
        $type = $this->getMockBuilder(ExampleType::class)
                    ->setMethods(['getFields'])
                    ->getMock();

        $type->expects($this->once())
            ->method('getFields');

        $attributes = $type->getAttributes();
        $attributes['fields']();
    }

    /**
     * Test to array
     *
     * @test
     */
    public function testToArray()
    {
        $type = new ExampleType();
        $array = $type->toArray();

        $this->assertInternalType('array', $array);

        $attributes = $type->getAttributes();
        $this->assertEquals($attributes, $array);
    }

    /**
     * Test to type
     *
     * @test
     */
    public function testToType()
    {
        $type = new ExampleType();
        $objectType = $type->toType();

        $this->assertInstanceOf(ObjectType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
