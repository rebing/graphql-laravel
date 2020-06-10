<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Support\Objects\ExampleFieldSelectFieldsResolve;
use Rebing\GraphQL\Tests\TestCase;

class FieldAuthorizeTest extends TestCase
{
    protected function getFieldClass()
    {
        return ExampleFieldSelectFieldsResolve::class;
    }

    /**
     * Ensure $getSelectFields is an instance of a closure when calling authorize
     */
    public function testSelectFieldsClosure(): void
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
            ->setMethods(['authorize'])
            ->getMock();

        $mockResolveInfo = new ResolveInfo(
            '',
            [],
            new ObjectType(['name' => 'test']),
            new ObjectType(['name' => 'test']),
            [],
            new Schema([]),
            [],
            null,
            null,
            []
        );

        $mockSelectFields = new SelectFields(
            $mockResolveInfo,
            Type::string(),
            [],
            1,
             null
        );

        $field->expects($this->once())
            ->method('authorize')
            ->with(null, [], [], $mockResolveInfo, function() {})
            ->willReturn(true);

        $attributes = $field->getAttributes();
        $selectFieldResult = $attributes['resolve'](null, [], [], $mockResolveInfo, $mockSelectFields);

        $this->assertInstanceOf(SelectFields::class, $selectFieldResult[0]);
    }
}
