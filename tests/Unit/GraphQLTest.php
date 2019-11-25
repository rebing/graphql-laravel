<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Exception\SchemaNotFound;
use Rebing\GraphQL\Exception\TypeNotFound;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Objects\CustomExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;
use Rebing\GraphQL\Tests\TestCase;
use Validator;

class GraphQLTest extends TestCase
{
    /**
     * Test schema default.
     */
    public function testSchema(): void
    {
        $schema = GraphQL::schema();

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examples');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExample');
        $this->assertArrayHasKey('Example', $schema->getTypeMap());
    }

    /**
     * Test schema with object.
     */
    public function testSchemaWithSchemaObject(): void
    {
        $schemaObject = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
            ]),
            'types' => [],
        ]);
        $schema = GraphQL::schema($schemaObject);

        $this->assertGraphQLSchema($schema);
    }

    /**
     * Test schema with name.
     */
    public function testSchemaWithName(): void
    {
        $schema = GraphQL::schema('custom');

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examplesCustom');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExampleCustom');
        $this->assertArrayHasKey('Example', $schema->getTypeMap());
    }

    /**
     * Test schema custom.
     */
    public function testSchemaWithArray(): void
    {
        $schema = GraphQL::schema([
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
            'types' => [
                CustomExampleType::class,
            ],
        ]);

        $this->assertGraphQLSchema($schema);
        $this->assertGraphQLSchemaHasQuery($schema, 'examplesCustom');
        $this->assertGraphQLSchemaHasMutation($schema, 'updateExampleCustom');
        $this->assertArrayHasKey('CustomExample', $schema->getTypeMap());
    }

    /**
     * Test schema with wrong name.
     */
    public function testSchemaWithWrongName(): void
    {
        $this->expectException(SchemaNotFound::class);
        GraphQL::schema('wrong');
    }

    /**
     * Test type.
     */
    public function testType(): void
    {
        $type = GraphQL::type('Example');
        $this->assertInstanceOf(ObjectType::class, $type);

        $typeOther = GraphQL::type('Example');
        $this->assertTrue($type === $typeOther);

        $typeOther = GraphQL::type('Example', true);
        $this->assertFalse($type === $typeOther);
    }

    /**
     * Test wrong type.
     */
    public function testWrongType(): void
    {
        $this->expectException(TypeNotFound::class);
        GraphQL::type('ExampleWrong');
    }

    /**
     * Test objectType.
     */
    public function testObjectType(): void
    {
        $objectType = new ObjectType([
            'name' => 'ObjectType',
        ]);
        $type = GraphQL::objectType($objectType, [
            'name' => 'ExampleType',
        ]);

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals($objectType, $type);
        $this->assertEquals($type->name, 'ExampleType');
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

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals($type->name, 'ExampleType');
        $fields = $type->getFields();
        $this->assertArrayHasKey('test', $fields);
    }

    public function testObjectTypeClass(): void
    {
        $type = GraphQL::objectType(ExampleType::class, [
            'name' => 'ExampleType',
        ]);

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals($type->name, 'ExampleType');
        $fields = $type->getFields();
        $this->assertArrayHasKey('test', $fields);
    }

    public function testFormatError(): void
    {
        $result = GraphQL::queryAndReturnResult($this->queries['examplesWithError']);
        $error = GraphQL::formatError($result->errors[0]);

        $this->assertIsArray($error);
        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('locations', $error);
        $expectedError = [
            'message' => 'Cannot query field "examplesQueryNotFound" on type "Query". Did you mean "examplesPagination"?',
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
        $this->assertEquals($expectedError, $error);
    }

    public function testFormatValidationError(): void
    {
        $validator = Validator::make([], [
            'test' => 'required',
        ]);
        $validator->fails();
        $validationError = new ValidationError('validation', $validator);
        $error = new Error('error', null, null, null, null, $validationError);
        $error = GraphQL::formatError($error);

        $this->assertIsArray($error);
        $this->assertArrayHasKey('extensions', $error);
        $this->assertArrayHasKey('validation', $error['extensions']);
        $this->assertTrue($error['extensions']['validation']->has('test'));
    }

    /**
     * Test add type.
     */
    public function testAddType(): void
    {
        GraphQL::addType(CustomExampleType::class);

        $types = GraphQL::getTypes();
        $this->assertArrayHasKey('CustomExample', $types);

        $type = app($types['CustomExample']);
        $this->assertInstanceOf(CustomExampleType::class, $type);

        $type = GraphQL::type('CustomExample');
        $this->assertInstanceOf(ObjectType::class, $type);
    }

    /**
     * Test add type with a name.
     */
    public function testAddTypeWithName(): void
    {
        GraphQL::addType(ExampleType::class, 'CustomExample');

        $types = GraphQL::getTypes();
        $this->assertArrayHasKey('CustomExample', $types);

        $type = app($types['CustomExample']);
        $this->assertInstanceOf(ExampleType::class, $type);

        $type = GraphQL::type('CustomExample');
        $this->assertInstanceOf(ObjectType::class, $type);
    }

    /**
     * Test get types.
     */
    public function testGetTypes(): void
    {
        $types = GraphQL::getTypes();
        $this->assertArrayHasKey('Example', $types);

        $type = app($types['Example']);
        $this->assertInstanceOf(ExampleType::class, $type);
    }

    /**
     * Test add schema.
     */
    public function testAddSchema(): void
    {
        GraphQL::addSchema('custom_add', [
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
            'types' => [
                CustomExampleType::class,
            ],
        ]);

        $schemas = GraphQL::getSchemas();
        $this->assertArrayHasKey('custom_add', $schemas);
    }

    /**
     * Test merge schema.
     */
    public function testMergeSchema(): void
    {
        GraphQL::addSchema('custom_add', [
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
            'types' => [
                CustomExampleType::class,
            ],
        ]);

        GraphQL::addSchema('custom_add_another', [
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
            'types' => [
                CustomExampleType::class,
            ],
        ]);

        $schemas = GraphQL::getSchemas();
        $this->assertArrayHasKey('custom_add', $schemas);
        $this->assertArrayHasKey('custom_add_another', $schemas);

        GraphQL::addSchema('custom_add_another', [
            'query' => [
                'examplesCustomAnother' => ExamplesQuery::class,
            ],
        ]);

        $schemas = GraphQL::getSchemas();
        $this->assertArrayHasKey('custom_add_another', $schemas);

        $querys = $schemas['custom_add_another']['query'];
        $this->assertArrayHasKey('examplesCustom', $querys);
        $this->assertArrayHasKey('examplesCustomAnother', $querys);
    }

    /**
     * Test get schemas.
     */
    public function testGetSchemas(): void
    {
        $schemas = GraphQL::getSchemas();
        $this->assertArrayHasKey('default', $schemas);
        $this->assertArrayHasKey('custom', $schemas);
        $this->assertIsArray($schemas['default']);
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
        $this->assertSame($expectedResult, $result);
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

        $this->assertSame($expectedResult, $result);
    }
}
