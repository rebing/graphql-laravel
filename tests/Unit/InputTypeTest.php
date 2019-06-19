<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;
use GraphQL\Type\Definition\InputObjectType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleInputType;

class InputTypeTest extends TestCase
{
    /**
     * Test to type.
     */
    public function testToType(): void
    {
        $type = new ExampleInputType();
        /** @var InputObjectType $objectType */
        $objectType = $type->toType();

        $this->assertInstanceOf(InputObjectType::class, $objectType);

        $this->assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        $this->assertArrayHasKey('test', $fields);
    }
}
