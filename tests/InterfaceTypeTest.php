<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;

class InterfaceTypeTest extends TestCase
{
    /**
     * Test get attributes
     *
     * @test
     */
    public function testGetAttributes()
    {
        $type = new ExampleInterfaceType();
        $attributes = $type->getAttributes();

        $this->assertArrayHasKey('resolveType', $attributes);
        $this->assertInstanceOf(Closure::class, $attributes['resolveType']);
    }

    /**
     * Test get attributes resolve type
     *
     * @test
     */
    public function testGetAttributesResolveType()
    {
        $type = $this->getMockBuilder(ExampleInterfaceType::class)
                    ->setMethods(['resolveType'])
                    ->getMock();

        $type->expects($this->once())
            ->method('resolveType');

        $attributes = $type->getAttributes();
        $attributes['resolveType'](null);
    }

    /**
     * Test to type
     *
     * @test
     */
    public function testToType()
    {
        $type = new ExampleInterfaceType();
        $interfaceType = $type->toType();

        $this->assertInstanceOf(InterfaceType::class, $interfaceType);

        $this->assertEquals($interfaceType->name, $type->name);

        $fields = $interfaceType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
