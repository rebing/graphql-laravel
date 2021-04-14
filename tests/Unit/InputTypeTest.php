<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\InputObjectType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleInputType;
use Rebing\GraphQL\Tests\TestCase;

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

        self::assertInstanceOf(InputObjectType::class, $objectType);

        self::assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        self::assertArrayHasKey('test', $fields);
    }
}
