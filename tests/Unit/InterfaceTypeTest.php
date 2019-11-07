<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use Rebing\GraphQL\Tests\TestCase;
use GraphQL\Type\Definition\InterfaceType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleInterfaceType;

class InterfaceTypeTest extends TestCase
{
    /**
     * Test get attributes.
     */
    public function testGetAttributes(): void
    {
        $type = new ExampleInterfaceType();
        $attributes = $type->getAttributes();

        $this->assertArrayHasKey('resolveType', $attributes);
        $this->assertInstanceOf(Closure::class, $attributes['resolveType']);
    }

    /**
     * Test get attributes resolve type.
     */
    public function testGetAttributesResolveType(): void
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
     * Test to type.
     */
    public function testToType(): void
    {
        $type = new ExampleInterfaceType();
        /** @var InterfaceType $interfaceType */
        $interfaceType = $type->toType();

        $this->assertInstanceOf(InterfaceType::class, $interfaceType);

        $this->assertEquals($interfaceType->name, $type->name);

        $fields = $interfaceType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
