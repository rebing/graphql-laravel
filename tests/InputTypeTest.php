<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests;

use GraphQL\Type\Definition\InputObjectType;
use Rebing\GraphQL\Tests\Objects\ExampleInputType;

class InputTypeTest extends TestCase
{
    /**
     * Test to type.
     */
    public function testToType()
    {
        $type = new ExampleInputType();
        $objectType = $type->toType();

        $this->assertInstanceOf(InputObjectType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
