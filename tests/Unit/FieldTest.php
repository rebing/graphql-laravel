<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Support\Objects\ExampleField;

class FieldTest extends TestCase
{
    protected function getFieldClass()
    {
        return ExampleField::class;
    }

    /**
     * Test get attributes.
     */
    public function testGetAttributes(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('args', $attributes);
        $this->assertArrayHasKey('resolve', $attributes);
        $this->assertIsArray($attributes['args']);
        $this->assertInstanceOf(Closure::class, $attributes['resolve']);
        $this->assertInstanceOf(get_class($field->type()), $attributes['type']);
    }

    /**
     * Test resolve closure.
     */
    public function testResolve(): void
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
     * Test to array.
     */
    public function testToArray(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $array = $field->toArray();

        $this->assertIsArray($array);

        $attributes = $field->getAttributes();
        $this->assertEquals($attributes, $array);
    }
}
