<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\EnumType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleEnumType;
use Rebing\GraphQL\Tests\TestCase;

class EnumTypeTest extends TestCase
{
    /**
     * Test to type.
     */
    public function testToType(): void
    {
        $type = new ExampleEnumType();
        /** @var EnumType $objectType */
        $objectType = $type->toType();

        $this->assertInstanceOf(EnumType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $typeValues = $type->toArray();
        $values = $objectType->getValues();
        $this->assertEquals(array_keys($typeValues['values'])[0], $values[0]->name);
        $this->assertEquals($typeValues['values']['TEST']['value'], $values[0]->value);
    }
}
