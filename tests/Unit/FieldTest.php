<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\MockObject\Stub;
use Rebing\GraphQL\Support\Field;
use Rebing\GraphQL\Tests\Support\Objects\ExampleField;
use Rebing\GraphQL\Tests\TestCase;

class FieldTest extends TestCase
{
    /**
     * @return class-string<Field>
     */
    protected function getFieldClass(): string
    {
        return ExampleField::class;
    }

    protected function resolveInfoMock(): Stub
    {
        return self::createStub(ResolveInfo::class);
    }

    public function testGetAttributes(): void
    {
        $class = $this->getFieldClass();
        $field = new $class;
        $attributes = $field->getAttributes();

        self::assertArrayHasKey('name', $attributes);
        self::assertArrayHasKey('type', $attributes);
        self::assertArrayHasKey('args', $attributes);
        self::assertArrayHasKey('resolve', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['resolve']);
        self::assertInstanceOf(\get_class($field->type()), $attributes['type']);
    }

    public function testResolve(): void
    {
        $class = $this->getFieldClass();
        $field = $this->createPartialMock($class, ['resolve']);

        $field->expects(self::once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [], [], $this->resolveInfoMock());
    }

    public function testToArray(): void
    {
        $class = $this->getFieldClass();
        $field = $this->createPartialMock($class, ['getAttributes']);
        $attributes = [
            'name' => 'example',
            'resolve' => static fn (): string => 'resolved',
        ];

        $field->expects(self::once())
            ->method('getAttributes')
            ->willReturn($attributes);

        self::assertSame($attributes, $field->toArray());
    }

    public function testDynamicAttributeAccess(): void
    {
        $class = $this->getFieldClass();
        $field = new $class;

        self::assertIsString($field->__get('name'));
        self::assertNull($field->__get('nonexistent_attribute'));
    }
}
