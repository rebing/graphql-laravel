<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class FieldTest extends TestCase
{
    protected function getFieldClass()
    {
        return ExampleField::class;
    }

    /**
     * Test get attributes
     *
     * @test
     */
    public function testGetAttributes()
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('args', $attributes);
        $this->assertArrayHasKey('resolve', $attributes);
        $this->assertInternalType('array', $attributes['args']);
        $this->assertInstanceOf(Closure::class, $attributes['resolve']);
        $this->assertInstanceOf(get_class($field->type()), $attributes['type']);
    }

    /**
     * Test resolve closure
     *
     * @test
     */
    public function testResolve()
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
                    ->setMethods(['resolve'])
                    ->getMock();

        $field->expects($this->once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [], [], null);
    }

    /**
     * Test to array
     *
     * @test
     */
    public function testToArray()
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $array = $field->toArray();

        $this->assertInternalType('array', $array);

        $attributes = $field->getAttributes();
        $this->assertEquals($attributes, $array);
    }
}
