<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Macroable;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Exception\SchemaNotFound;
use Rebing\GraphQL\Exception\TypeNotFound;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Directives\ExampleDirective;
use Rebing\GraphQL\Tests\Support\Objects\CustomExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Rebing\GraphQL\Tests\TestCase;

class GraphQLTest extends TestCase
{
    public function testDefaultSchema(): void
    {
        $schema = GraphQL::schema();

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examples');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExample');
        self::assertArrayHasKey('Example', $schema->getTypeMap());
    }

    public function testSchemaWithName(): void
    {
        $schema = GraphQL::schema('custom');

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examplesCustom');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExampleCustom');
        self::assertArrayHasKey('Example', $schema->getTypeMap());
    }

    public function testSchemaWithNameReferencingClass(): void
    {
        $schema = GraphQL::schema('class_based');

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examplesCustom');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExampleCustom');
        self::assertArrayHasKey('Example', $schema->getTypeMap());
    }

    public function testSchemaWithWrongName(): void
    {
        $this->expectException(SchemaNotFound::class);
        GraphQL::schema('wrong');
    }

    public function testSchemaWithInvalidClassName(): void
    {
        $this->app['config']->set('graphql.schemas.invalid_class_based', 'ThisClassDoesntExist');

        $this->expectException(SchemaNotFound::class);
        $this->expectExceptionMessage("Cannot find class 'ThisClassDoesntExist' for schema 'invalid_class_based'");
        GraphQL::schema('invalid_class_based');
    }

    public function testType(): void
    {
        $type = GraphQL::type('Example');
        self::assertInstanceOf(ObjectType::class, $type);

        $typeOther = GraphQL::type('Example');
        self::assertSame($type, $typeOther);

        $typeOther = GraphQL::type('Example', true);
        self::assertNotSame($type, $typeOther);
    }

    public function testWrongType(): void
    {
        $this->expectException(TypeNotFound::class);
        GraphQL::type('ExampleWrong');
    }

    public function testNonNullType(): void
    {
        /** @var NonNull */
        $type = GraphQL::type('Example!');
        self::assertInstanceOf(NonNull::class, $type);

        /** @var NonNull */
        $typeOther = GraphQL::type('Example!');
        self::assertSame($type->getWrappedType(), $typeOther->getWrappedType());

        /** @var NonNull */
        $typeOther = GraphQL::type('Example!', true);
        self::assertNotSame($type->getWrappedType(), $typeOther->getWrappedType());
    }

    public function testListOfType(): void
    {
        /** @var ListOfType */
        $type = GraphQL::type('[Example]');
        self::assertInstanceOf(ListOfType::class, $type);

        /** @var ListOfType */
        $typeOther = GraphQL::type('[Example]');
        self::assertSame($type->getWrappedType(), $typeOther->getWrappedType());

        /** @var ListOfType */
        $typeOther = GraphQL::type('[Example]', true);
        self::assertNotSame($type->getWrappedType(), $typeOther->getWrappedType());
    }

    public function testListOfNonNullType(): void
    {
        /** @var ListOfType */
        $type = GraphQL::type('[Example!]');
        self::assertInstanceOf(ListOfType::class, $type);
        self::assertInstanceOf(NonNull::class, $type->getWrappedType());

        /** @var ListOfType */
        $typeOther = GraphQL::type('[Example!]');
        self::assertSame($type->getWrappedType(true), $typeOther->getWrappedType(true));

        /** @var ListOfType */
        $typeOther = GraphQL::type('[Example!]', true);
        self::assertNotSame($type->getWrappedType(true), $typeOther->getWrappedType(true));
    }

    public function testNonNullListOfNonNullType(): void
    {
        /** @var NonNull */
        $type = GraphQL::type('[Example!]!');
        /** @var ListOfType */
        $wrappedType = $type->getWrappedType();

        self::assertInstanceOf(NonNull::class, $type);
        self::assertInstanceOf(ListOfType::class, $wrappedType);
        self::assertInstanceOf(NonNull::class, $wrappedType->getWrappedType());

        /** @var NonNull */
        $typeOther = GraphQL::type('[Example!]!');
        self::assertSame($type->getWrappedType(true), $typeOther->getWrappedType(true));

        /** @var NonNull */
        $typeOther = GraphQL::type('[Example!]!', true);
        self::assertNotSame($type->getWrappedType(true), $typeOther->getWrappedType(true));
    }

    public function testMalformedListOfWithNoLeadingBracket(): void
    {
        $this->expectException(TypeNotFound::class);
        $this->expectExceptionMessage('Type Example] not found.');
        GraphQL::type('Example]');
    }

    public function testMalformedListOfWithNoTrailingBracket(): void
    {
        $this->expectException(TypeNotFound::class);
        $this->expectExceptionMessage('Type [Example not found.');
        GraphQL::type('[Example');
    }

    public function testMalformedNonNullListOfWithNoTrailingBracket(): void
    {
        $this->expectException(TypeNotFound::class);
        $this->expectExceptionMessage('Type [Example not found.');
        GraphQL::type('[Example!');
    }

    public function testEmptyListOfType(): void
    {
        $this->expectException(TypeNotFound::class);
        $this->expectExceptionMessage('Type [] not found.');
        GraphQL::type('[]');
    }

    public function testEmptyNonNull(): void
    {
        $this->expectException(TypeNotFound::class);
        $this->expectExceptionMessage('Type ! not found.');
        GraphQL::type('!');
    }

    public function testStandardTypes(): void
    {
        $standardTypes = Type::getStandardTypes();

        foreach ($standardTypes as $standardType) {
            $type = GraphQL::type($standardType->name);
            self::assertSame($standardType, $type);

            $typeOther = GraphQL::type($type->name);
            self::assertSame($type, $typeOther);

            $typeOther = GraphQL::type($type->name, true);
            self::assertSame($type, $typeOther);
        }
    }

    public function testStandardTypeModifiers(): void
    {
        $standardTypes = Type::getStandardTypes();

        foreach ($standardTypes as $standardType) {
            /** @var NonNull */
            $type = GraphQL::type("$standardType->name!");

            self::assertInstanceOf(NonNull::class, $type);
            self::assertSame($type->getWrappedType(), $standardType);

            /** @var ListOfType */
            $type = GraphQL::type("[$standardType->name]");

            self::assertInstanceOf(ListOfType::class, $type);
            self::assertSame($type->getWrappedType(), $standardType);

            /** @var ListOfType */
            $type = GraphQL::type("[$standardType->name!]");

            self::assertInstanceOf(ListOfType::class, $type);
            self::assertInstanceOf(NonNull::class, $type->getWrappedType());
            self::assertSame($type->getWrappedType(true), $standardType);

            /** @var NonNull */
            $type = GraphQL::type("[$standardType->name!]!");
            /** @var ListOfType */
            $wrappedType = $type->getWrappedType();

            self::assertInstanceOf(NonNull::class, $type);
            self::assertInstanceOf(ListOfType::class, $wrappedType);
            self::assertInstanceOf(NonNull::class, $wrappedType->getWrappedType());
            self::assertSame($type->getWrappedType(true), $standardType);
        }
    }

    public function testObjectType(): void
    {
        $objectType = new ObjectType([
            'name' => 'ObjectType',
        ]);
        $type = GraphQL::objectType($objectType, [
            'name' => 'ExampleType',
        ]);

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertEquals($objectType, $type);
        self::assertEquals('ExampleType', $type->name);
    }

    public function testObjectTypeFromFields(): void
    {
        $type = GraphQL::objectType([
            'test' => [
                'type' => Type::string(),
                'description' => 'A test field',
            ],
        ], [
            'name' => 'ExampleType',
        ]);

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertEquals('ExampleType', $type->name);
        $fields = $type->getFields();
        self::assertArrayHasKey('test', $fields);
    }

    public function testObjectTypeClass(): void
    {
        $type = GraphQL::objectType(ExampleType::class, [
            'name' => 'ExampleType',
        ]);

        self::assertInstanceOf(ObjectType::class, $type);
        self::assertEquals('ExampleType', $type->name);
        $fields = $type->getFields();
        self::assertArrayHasKey('test', $fields);
    }

    public function testFormatError(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithError']);
        $error = GraphQL::formatError($result->errors[0]);

        self::assertArrayHasKey('message', $error);
        self::assertArrayHasKey('locations', $error);
        $expectedError = [
            'message' => 'Cannot query field "examplesQueryNotFound" on type "Query".',
            'extensions' => [
                'category' => 'graphql',
            ],
            'locations' => [
                [
                    'line' => 3,
                    'column' => 13,
                ],
            ],
        ];
        self::assertEquals($expectedError, $error);
    }

    public function testFormatValidationError(): void
    {
        $validator = Validator::make([], [
            'test' => 'required',
        ]);
        $validator->fails();
        $validationError = new ValidationError('validation', $validator);
        $error = new Error('error', null, null, [], null, $validationError);
        $error = GraphQL::formatError($error);

        self::assertArrayHasKey('trace', $error);
        unset($error['trace']);

        $expected = [
            'message' => 'error',
            'extensions' => [
                'category' => 'validation',
                'validation' => [
                    'test' => [
                        'The test field is required.',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $error);
    }

    public function testAddType(): void
    {
        GraphQL::addType(CustomExampleType::class);

        $types = GraphQL::getTypes();
        self::assertArrayHasKey('CustomExample', $types);

        $type = app($types['CustomExample']);
        self::assertInstanceOf(CustomExampleType::class, $type);

        $type = GraphQL::type('CustomExample');
        self::assertInstanceOf(ObjectType::class, $type);
    }

    public function testAddTypeWithName(): void
    {
        GraphQL::addType(ExampleType::class, 'CustomExample');

        $types = GraphQL::getTypes();
        self::assertArrayHasKey('CustomExample', $types);

        $type = app($types['CustomExample']);
        self::assertInstanceOf(ExampleType::class, $type);

        $type = GraphQL::type('CustomExample');
        self::assertInstanceOf(ObjectType::class, $type);
    }

    public function testGetTypes(): void
    {
        $types = GraphQL::getTypes();
        self::assertArrayHasKey('Example', $types);

        $type = app($types['Example']);
        self::assertInstanceOf(ExampleType::class, $type);
    }

    public function testAddSchema(): void
    {
        $schema = GraphQL::buildSchemaFromConfig(
            [
                'query' => [
                    'examplesCustom' => ExamplesQuery::class,
                ],
                'mutation' => [
                    'updateExampleCustom' => UpdateExampleMutation::class,
                ],
                'types' => [
                    CustomExampleType::class,
                ],
            ]
        );
        GraphQL::addSchema('custom_add', $schema);

        $schemas = GraphQL::getSchemas();
        self::assertArrayHasKey('custom_add', $schemas);
    }

    public function testAddSchemaObjectAndExecuteQuery(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'testQuery' => [
                        'type' => Type::string(),
                        'resolve' => function () {
                            return 'Returning test data';
                        },
                    ],
                ],
            ]),
        ]);

        GraphQL::addSchema('schema_from_object', $schema);

        $result = GraphQL::query('{ testQuery }', null, [
            'schema' => 'schema_from_object',
        ]);

        $expectedResult = [
            'data' => [
                'testQuery' => 'Returning test data',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    public function testAddSchemaObjectAndExecuteQueryWithRootValue(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'testQuery' => [
                        'type' => Type::string(),
                        'resolve' => function ($root) {
                            return strtolower($root['testQuery']);
                        },
                    ],
                ],
            ]),
        ]);

        GraphQL::addSchema('schema_from_object', $schema);

        $result = GraphQL::query('{ testQuery }', null, [
            'schema' => 'schema_from_object',
            'rootValue' => [
                'testQuery' => 'CONVERTED TO LOWERCASE',
            ],
        ]);

        $expectedResult = [
            'data' => [
                'testQuery' => 'converted to lowercase',
            ],
        ];

        self::assertSame($expectedResult, $result);
    }

    public function testBuildSchemaWithDirectives(): void
    {
        $schema = GraphQL::buildSchemaFromConfig([
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'directives' => [
                ExampleDirective::class,
            ],
        ]);

        self::assertSame([
            'include',
            'skip',
            'deprecated',
            'exampleDirective',
        ], array_keys($schema->getDirectives()));
    }

    public function testIsMacroable(): void
    {
        self::assertContains(Macroable::class, class_uses_recursive(GraphQL::getFacadeRoot()));
    }
}
