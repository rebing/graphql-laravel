<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;

class TypeTest extends TestCase
{
    public function testGetFields(): void
    {
        $type = new ExampleType();
        $fields = $type->getFields();

        self::assertArrayHasKey('test', $fields);
        self::assertEquals($fields['test'], [
            'type' => Type::string(),
            'description' => 'A test field',
        ]);
    }

    public function testGetAttributes(): void
    {
        $type = new ExampleType();
        $attributes = $type->getAttributes();

        self::assertArrayHasKey('name', $attributes);
        self::assertArrayHasKey('fields', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['fields']);
        self::assertIsArray($attributes['fields']());
    }

    public function testGetAttributesFieldsClosure(): void
    {
        $type = $this->getMockBuilder(ExampleType::class)
                    ->setMethods(['getFields'])
                    ->getMock();

        $type->expects(self::once())
            ->method('getFields');

        $attributes = $type->getAttributes();
        $attributes['fields']();
    }

    public function testToArray(): void
    {
        $type = new ExampleType();
        $array = $type->toArray();

        self::assertIsArray($array);

        $attributes = $type->getAttributes();
        self::assertEquals($attributes, $array);
    }

    public function testToType(): void
    {
        $type = new ExampleType();
        /** @var ObjectType $objectType */
        $objectType = $type->toType();

        self::assertInstanceOf(ObjectType::class, $objectType);

        self::assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        self::assertArrayHasKey('test', $fields);
    }
}
