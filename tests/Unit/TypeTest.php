<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Tests\TestCase;
use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;

class TypeTest extends TestCase
{
    /**
     * Test getFields.
     */
    public function testGetFields(): void
    {
        $type = new ExampleType();
        $fields = $type->getFields();

        $this->assertArrayHasKey('test', $fields);
        $this->assertEquals($fields['test'], [
            'type' => Type::string(),
            'description' => 'A test field',
        ]);
    }

    /**
     * Test get attributes.
     */
    public function testGetAttributes(): void
    {
        $type = new ExampleType();
        $attributes = $type->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('fields', $attributes);
        $this->assertInstanceOf(Closure::class, $attributes['fields']);
        $this->assertIsArray($attributes['fields']());
    }

    /**
     * Test get attributes fields closure.
     */
    public function testGetAttributesFields(): void
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
     * Test to array.
     */
    public function testToArray(): void
    {
        $type = new ExampleType();
        $array = $type->toArray();

        $this->assertIsArray($array);

        $attributes = $type->getAttributes();
        $this->assertEquals($attributes, $array);
    }

    /**
     * Test to type.
     */
    public function testToType(): void
    {
        $type = new ExampleType();
        /** @var ObjectType $objectType */
        $objectType = $type->toType();

        $this->assertInstanceOf(ObjectType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
