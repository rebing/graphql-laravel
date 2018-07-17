<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\EnumType;

class EnumTypeTest extends TestCase
{
    /**
     * Test to type
     *
     * @test
     */
    public function testToType()
    {
        $type = new ExampleEnumType();
        $objectType = $type->toType();

        $this->assertInstanceOf(EnumType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $typeValues = $type->toArray();
        $values = $objectType->getValues();
        $this->assertEquals(array_keys($typeValues['values'])[0], $values[0]->name);
        $this->assertEquals($typeValues['values']['TEST']['value'], $values[0]->value);
    }
}
