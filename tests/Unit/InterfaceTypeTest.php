<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\InterfaceType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleInterfaceType;
use Rebing\GraphQL\Tests\TestCase;

class InterfaceTypeTest extends TestCase
{
    public function testGetAttributes(): void
    {
        $type = new ExampleInterfaceType();
        $attributes = $type->getAttributes();

        self::assertArrayHasKey('resolveType', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['resolveType']);
    }

    public function testGetAttributesResolveType(): void
    {
        $type = $this->getMockBuilder(ExampleInterfaceType::class)
                    ->onlyMethods(['resolveType'])
                    ->getMock();

        $type->expects(self::once())
            ->method('resolveType');

        $attributes = $type->getAttributes();
        $attributes['resolveType'](null);
    }

    public function testToType(): void
    {
        $type = new ExampleInterfaceType();
        /** @var InterfaceType $interfaceType */
        $interfaceType = $type->toType();

        self::assertInstanceOf(InterfaceType::class, $interfaceType);

        self::assertEquals($interfaceType->name, $type->name);

        $fields = $interfaceType->getFields();
        self::assertArrayHasKey('test', $fields);
    }
}
