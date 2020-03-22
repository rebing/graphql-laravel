<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\Validator;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Tests\Support\Objects\ExampleNestedValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleRuleTestingInputObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutationForRuleTesting;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutationWithInputType;
use PHPUnit\Framework\MockObject\MockObject;

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
            'ExampleRuleTestingInputObject' => ExampleRuleTestingInputObject::class,
            'ExampleNestedValidationInputObject' => ExampleNestedValidationInputObject::class,
        ]);
    }

    protected function resolveInfoMock(): MockObject
    {
        return $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
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
                'otherValue' =>'134',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
            'test_with_rules_non_nullable_input_object' => [
                'val' => 'test',
                'otherValue' =>'134',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
        ], [], $this->resolveInfoMock());
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
        $attributes['resolve'](null, [], [], $this->resolveInfoMock());
    }

    /**
     * Test validation error.
     */
    public function testValidationError(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();
        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_non_nullable_input_object' => [
                    'val' => 4,
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        $this->assertTrue($messages->has('test'));
        $this->assertTrue($messages->has('test_with_rules'));
        $this->assertTrue($messages->has('test_with_rules_closure'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        $this->assertCount(6, $messages->all());
    }

    public function testWithInput(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_non_nullable_input_object' => [
                    'val' => 4,
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $e) {
            $validator = $e->getValidator();

            $this->assertInstanceOf(Validator::class, $validator);

            $messages = $e->getValidatorMessages();

            $this->assertTrue($messages->has('test'));
            $this->assertTrue($messages->has('test_with_rules'));
            $this->assertTrue($messages->has('test_with_rules_closure'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
            $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
            $this->assertCount(6, $messages->all());
        }
    }

    public function testWithEmptyInput(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;

        try {
            $attributes['resolve'](null, [], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        $this->assertTrue($messages->has('test'));
        $this->assertTrue($messages->has('test_with_rules'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        $this->assertTrue($messages->has('test_with_rules_closure'));
        $this->assertCount(4, $messages->all());
    }

    public function testWithInputDepthOne(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;

        try {
            $attributes['resolve'](null, [
                'test_with_rules' => 'test',
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        $this->assertTrue($messages->has('test'));

        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        $this->assertTrue($messages->has('test_with_rules_closure'));
        $this->assertCount(3, $messages->all());
    }

    public function testWithInputWithEmptyInputObjects(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_non_nullable_input_object' => [],
                'test_with_rules_nullable_input_object' => [],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        $this->assertTrue($messages->has('test'));

        $this->assertTrue($messages->has('test_with_rules_closure'));

        $this->assertTrue($messages->has('test_with_rules'));

        $this->assertTrue($messages->has('test_with_rules_nullable_input_object.otherValue'));
        $this->assertTrue($messages->has('test_with_rules_nullable_input_object.val'));
        $this->assertTrue($messages->has('test_with_rules_nullable_input_object.nest'));
        $this->assertTrue($messages->has('test_with_rules_nullable_input_object.list'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.val'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        $this->assertCount(12, $messages->all());
    }

    public function testWithEmptyArrayOfInputsObjects(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_non_nullable_list_of_non_nullable_input_object' => [],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();
        $this->assertTrue($messages->has('test'));
        $this->assertTrue($messages->has('test_with_rules'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        $this->assertTrue($messages->has('test_with_rules_closure'));
        $this->assertCount(4, $messages->all());
    }

    public function testWithArrayOfInputsObjects(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_non_nullable_list_of_non_nullable_input_object' => [
                    [
                        'val' => 1245,
                    ],
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $validator = $exception->getValidator();

        $this->assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        $this->assertTrue($messages->has('test'));
        $this->assertTrue($messages->has('test_with_rules'));
        $this->assertTrue($messages->has('test_with_rules_closure'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.otherValue'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.nest'));
        $this->assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.list'));
        $this->assertCount(7, $messages->all());
    }

    /**
     * Test custom validation error messages.
     */
    public function testCustomValidationErrorMessages(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();

        /** @var ValidationError $exception */
        $exception = null;
        try {
            $attributes['resolve'](null, [
                'test_with_rules_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
                'test_with_rules_non_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
        }

        $messages = $exception->getValidatorMessages();

        $this->assertEquals($messages->first('test'), 'The test field is required.');
        $this->assertEquals($messages->first('test_with_rules_nullable_input_object.nest.email'), 'The test with rules nullable input object.nest.email must be a valid email address.');
        $this->assertEquals($messages->first('test_with_rules_non_nullable_input_object.nest.email'), 'The test with rules non nullable input object.nest.email must be a valid email address.');
    }

    public function testRuleCallbackArgumentsMatchesTheInput(): void
    {
        $this->expectException(ValidationError::class);

        $field = new UpdateExampleMutationForRuleTesting;
        $attributes = $field->getAttributes();

        $attributes['resolve'](null, [
            'test_with_rules_callback_params' => [
                'otherValue' => 1337,
            ],
        ], [], $this->resolveInfoMock());
    }
}
