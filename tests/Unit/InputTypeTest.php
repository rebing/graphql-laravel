<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\InputObjectType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleInputType;
use Rebing\GraphQL\Tests\TestCase;

class InputTypeTest extends TestCase
{
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

    public function testOneOfAttribute(): void
    {
        $type = new class extends \Rebing\GraphQL\Support\InputType {
            protected $attributes = [
                'name' => 'TestOneOfInput',
                'isOneOf' => true,
            ];

            public function fields(): array
            {
                return [
                    'byId' => \GraphQL\Type\Definition\Type::id(),
                    'byEmail' => \GraphQL\Type\Definition\Type::string(),
                ];
            }
        };

        /** @var InputObjectType $objectType */
        $objectType = $type->toType();

        self::assertInstanceOf(InputObjectType::class, $objectType);
        self::assertTrue($objectType->isOneOf);
    }

    public function testNonOneOfIsDefault(): void
    {
        $type = new ExampleInputType();
        /** @var InputObjectType $objectType */
        $objectType = $type->toType();

        self::assertInstanceOf(InputObjectType::class, $objectType);
        self::assertFalse($objectType->isOneOf);
    }
}
