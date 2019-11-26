<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Tests\Support\Objects\ExampleNestedValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutationWithInputType;

class MutationTest extends FieldTest
{
    protected function getFieldClass()
    {
        return UpdateExampleMutationWithInputType::class;
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
            'ExampleValidationInputObject' => ExampleValidationInputObject::class,
            'ExampleNestedValidationInputObject' => ExampleNestedValidationInputObject::class,
        ]);
    }

    /**
     * Test get rules.
     */
    public function testGetRules(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $rules = $field->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('test', $rules);
        $this->assertArrayHasKey('test_with_rules', $rules);
        $this->assertArrayHasKey('test_with_rules_closure', $rules);
        $this->assertEquals($rules['test'], ['required']);
        $this->assertEquals($rules['test_with_rules'], ['required']);
        $this->assertEquals($rules['test_with_rules_closure'], ['required']);
        $this->assertEquals($rules['test_with_rules_nullable_input_object'], ['nullable']);
        $this->assertNull(Arr::get($rules, 'test_with_rules_nullable_input_object.val'));
        $this->assertNull(Arr::get($rules, 'test_with_rules_nullable_input_object.nest'));
        $this->assertNull(Arr::get($rules, 'test_with_rules_nullable_input_object.nest.email'));
        $this->assertNull(Arr::get($rules, 'test_with_rules_nullable_input_object.list'));
        $this->assertNull(Arr::get($rules, 'test_with_rules_nullable_input_object.list.*.email'));
        $this->assertEquals($rules['test_with_rules_non_nullable_input_object'], ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_input_object.val'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_input_object.nest'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_input_object.nest.email'), ['email']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_input_object.list'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_input_object.list.*.email'), ['email']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_list_of_non_nullable_input_object.*.val'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_list_of_non_nullable_input_object.*.nest'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_list_of_non_nullable_input_object.*.nest.email'), ['email']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_list_of_non_nullable_input_object.*.list'), ['required']);
        $this->assertEquals(Arr::get($rules, 'test_with_rules_non_nullable_list_of_non_nullable_input_object.*.list.*.email'), ['email']);
    }

    /**
     * Test resolve.
     */
    public function testResolve(): void
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
                    ->setMethods(['resolve'])
                    ->getMock();

        $field->expects($this->once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [
            'test' => 'test',
            'test_with_rules' => 'test',
            'test_with_rules_closure' => 'test',
            'test_with_rules_nullable_input_object' => [
                'val' => 'test',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
            'test_with_rules_non_nullable_input_object' => [
                'val' => 'test',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
        ], [], null);
    }

    /**
     * Test resolve throw validation error.
     */
    public function testResolveThrowValidationError(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();
        $this->expectException(ValidationError::class);
        $attributes['resolve'](null, [], [], null);
    }

    /**
     * Test validation error.
     */
    public function testValidationError(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        try {
            $attributes['resolve'](null, [], [], null);
        } catch (ValidationError $e) {
            $validator = $e->getValidator();

            $this->assertInstanceOf(Validator::class, $validator);

            $messages = $e->getValidatorMessages();
            $this->assertTrue($messages->has('test'));
            $this->assertTrue($messages->has('test_with_rules'));
            $this->assertTrue($messages->has('test_with_rules_closure'));
            $this->assertFalse($messages->has('test_with_rules_nullable_input_object.val'));
            $this->assertFalse($messages->has('test_with_rules_nullable_input_object.nest'));
            $this->assertFalse($messages->has('test_with_rules_nullable_input_object.list'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.val'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        }
    }

    /**
     * Test custom validation error messages.
     */
    public function testCustomValidationErrorMessages(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();

        try {
            $attributes['resolve'](null, [
                'test_with_rules_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
                'test_with_rules_non_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
             ], [], null);
        } catch (ValidationError $e) {
            $messages = $e->getValidatorMessages();

            $this->assertEquals($messages->first('test'), 'The test field is required.');
            $this->assertEquals($messages->first('test_with_rules_nullable_input_object.nest.email'), 'The test with rules nullable input object.nest.email must be a valid email address.');
            $this->assertEquals($messages->first('test_with_rules_non_nullable_input_object.nest.email'), 'The test with rules non nullable input object.nest.email must be a valid email address.');
        }
    }
}
