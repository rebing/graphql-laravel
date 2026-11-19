<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\EnumType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleEnumType;
use Rebing\GraphQL\Tests\TestCase;

class EnumTypeTest extends TestCase
{
    public function testToType(): void
    {
        $type = new ExampleEnumType();
        /** @var EnumType $objectType */
        $objectType = $type->toType();

        self::assertInstanceOf(EnumType::class, $objectType);

        self::assertEquals($objectType->name, $type->name);

        $typeValues = $type->toArray();
        $values = $objectType->getValues();
        self::assertEquals(array_keys($typeValues['values'])[0], $values[0]->name);
        self::assertEquals($typeValues['values']['TEST']['value'], $values[0]->value);
    }
}
