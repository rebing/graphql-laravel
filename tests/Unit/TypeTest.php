<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use EliasHaeussler\DeepClosureComparator\DeepClosureAssert;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;
use Rebing\GraphQL\Support\Type as GraphQLType;
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
        $type = $this->createPartialMock(ExampleType::class, ['getFields']);

        $type->expects(self::once())
            ->method('getFields');

        $attributes = $type->getAttributes();
        $attributes['fields']();
    }

    public function testGetAttributesInterfacesClosure(): void
    {
        $type = $this->createPartialMock(ExampleType::class, ['interfaces']);

        $type->expects(self::once())
            ->method('interfaces');

        $attributes = $type->getAttributes();
        $attributes['interfaces']();
    }

    public function testToArray(): void
    {
        $type = new ExampleType();
        $array = $type->toArray();

        $attributes = $type->getAttributes();
        DeepClosureAssert::assertEquals($attributes, $array);
    }

    public function testToType(): void
    {
        $type = new ExampleType();
        $objectType = $type->toType();

        self::assertInstanceOf(ObjectType::class, $objectType);

        self::assertEquals($objectType->name, $type->name);

        $fields = $objectType->getFields();
        self::assertArrayHasKey('test', $fields);
    }

    public function testGetFieldResolverAutoDiscovery(): void
    {
        $type = new class() extends GraphQLType {
            protected $attributes = ['name' => 'ResolverDiscovery'];

            public function fields(): array
            {
                return [
                    'full_name' => [
                        'type' => Type::string(),
                    ],
                ];
            }

            public function resolveFullNameField(): string
            {
                return 'John Doe';
            }
        };

        $fields = $type->getFields();
        self::assertArrayHasKey('full_name', $fields);
        self::assertArrayHasKey('resolve', $fields['full_name']);
        self::assertIsCallable($fields['full_name']['resolve']);
        self::assertSame('John Doe', $fields['full_name']['resolve'](null, [], null, null));
    }

    public function testGetFieldResolverAlias(): void
    {
        $type = new class() extends GraphQLType {
            protected $attributes = ['name' => 'AliasResolver'];

            public function fields(): array
            {
                return [
                    'display_name' => [
                        'type' => Type::string(),
                        'alias' => 'real_name',
                    ],
                ];
            }
        };

        $fields = $type->getFields();
        self::assertArrayHasKey('display_name', $fields);
        self::assertArrayHasKey('resolve', $fields['display_name']);
        self::assertIsCallable($fields['display_name']['resolve']);

        $source = (object) ['real_name' => 'Jane Doe'];
        self::assertSame('Jane Doe', $fields['display_name']['resolve']($source));
    }

    public function testGetFieldResolverAliasIgnoredForInputType(): void
    {
        $type = new class() extends InputType {
            protected $attributes = ['name' => 'AliasInputResolver'];

            public function fields(): array
            {
                return [
                    'display_name' => [
                        'type' => Type::string(),
                        'alias' => 'real_name',
                    ],
                ];
            }
        };

        $fields = $type->getFields();
        self::assertArrayHasKey('display_name', $fields);
        // InputType should NOT get an alias resolver
        self::assertArrayNotHasKey('resolve', $fields['display_name']);
    }
}
