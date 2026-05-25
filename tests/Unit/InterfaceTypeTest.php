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
        $type = new ExampleInterfaceType;
        $attributes = $type->getAttributes();

        self::assertArrayHasKey('resolveType', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['resolveType']);
    }

    public function testGetAttributesResolveType(): void
    {
        $type = $this->createPartialMock(ExampleInterfaceType::class, ['resolveType']);

        $type->expects(self::once())
            ->method('resolveType');

        $attributes = $type->getAttributes();
        $attributes['resolveType'](null);
    }

    public function testToType(): void
    {
        $type = new ExampleInterfaceType;
        $interfaceType = $type->toType();

        self::assertInstanceOf(InterfaceType::class, $interfaceType);

        self::assertEquals($interfaceType->name, $type->name);

        $fields = $interfaceType->getFields();
        self::assertArrayHasKey('test', $fields);
    }

    public function testGetAttributesIncludesTypesResolverWhenDefined(): void
    {
        // Covers `InterfaceType::getTypesResolver()`. The shared
        // ExampleInterfaceType fixture does NOT define `types()`, so this
        // path was previously uncovered. Here we use an inline subclass
        // that defines `types()` to assert the resolver is wired up.
        $type = new class extends \Rebing\GraphQL\Support\InterfaceType {
            protected $attributes = ['name' => 'AnimalInterface'];

            public function fields(): array
            {
                return [
                    'name' => ['type' => \GraphQL\Type\Definition\Type::string()],
                ];
            }

            /**
             * @return list<\GraphQL\Type\Definition\Type>
             */
            public function types(): array
            {
                return [
                    \GraphQL\Type\Definition\Type::string(),
                ];
            }

            public function resolveType(mixed $root): \GraphQL\Type\Definition\Type
            {
                return \GraphQL\Type\Definition\Type::string();
            }
        };

        $attributes = $type->getAttributes();

        self::assertArrayHasKey('types', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['types']);

        $resolved = $attributes['types']();
        self::assertCount(1, $resolved);
    }

    public function testGetAttributesOmitsTypesResolverWhenNotDefined(): void
    {
        // The default ExampleInterfaceType has no `types()` method, so
        // getTypesResolver() must return null and `types` must not be set.
        $type = new ExampleInterfaceType;
        $attributes = $type->getAttributes();

        self::assertArrayNotHasKey('types', $attributes);
    }
}
