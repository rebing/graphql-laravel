<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Tests\Support\Objects\ExampleNestedValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleRuleTestingInputObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\Support\Objects\ExampleValidationInputObject;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutationForRuleTesting;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutationWithInputType;

class MutationTest extends FieldTest
{
    /**
     * @return class-string<UpdateExampleMutationWithInputType>
     */
    protected function getFieldClass()
    {
        return UpdateExampleMutationWithInputType::class;
    }

    protected function getEnvironmentSetUp($app): void
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

    public function testResolve(): void
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
                    ->onlyMethods(['resolve'])
                    ->getMock();

        $field->expects(self::once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [
            'test' => 'test',
            'test_with_rules' => 'test',
            'test_with_rules_closure' => 'test',
            'test_with_rules_nullable_input_object' => [
                'val' => 'test',
                'otherValue' => '134',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
            'test_with_rules_non_nullable_input_object' => [
                'val' => 'test',
                'otherValue' => '134',
                'nest' => ['email' => 'test@test.com'],
                'list' => [
                    ['email' => 'test@test.com'],
                ],
            ],
            'test_validation_custom_attributes' => 'test',
        ], [], $this->resolveInfoMock());
    }

    public function testResolveThrowValidationError(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();
        $this->expectException(ValidationError::class);
        $attributes['resolve'](null, [], [], $this->resolveInfoMock());
    }

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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(7, $messages->all());
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
        } catch (ValidationError $exception) {
            // Deliberately empty
        }

        self::assertInstanceOf(ValidationError::class, $exception);

        $validator = $exception->getValidator();

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(7, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(5, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));

        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(4, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));

        self::assertTrue($messages->has('test_with_rules_closure'));

        self::assertTrue($messages->has('test_with_rules'));

        self::assertTrue($messages->has('test_validation_custom_attributes'));

        self::assertTrue($messages->has('test_with_rules_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.val'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.list'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.val'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        self::assertCount(13, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();
        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(5, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.otherValue'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.nest'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_list_of_non_nullable_input_object.0.list'));
        self::assertTrue($messages->has('test_validation_custom_attributes'));
        self::assertCount(8, $messages->all());
    }

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

        self::assertEquals('The test field is required.', $messages->first('test'));
        self::assertEquals(
            'The test with rules nullable input object.nest.email must be a valid email address.',
            $messages->first('test_with_rules_nullable_input_object.nest.email')
        );
        self::assertEquals(
            'The test with rules non nullable input object.nest.email must be a valid email address.',
            $messages->first('test_with_rules_non_nullable_input_object.nest.email')
        );
    }

    public function testCustomValidationAttributes(): void
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

        $messages = $exception->getValidatorMessages();

        self::assertEquals('The custom attribute field is required.', $messages->first('test_validation_custom_attributes'));
    }

    public function testRuleCallbackArgumentsMatchesTheInput(): void
    {
        $this->expectException(ValidationError::class);

        $field = new UpdateExampleMutationForRuleTesting();
        $attributes = $field->getAttributes();

        $attributes['resolve'](null, [
            'test_with_rules_callback_params' => [
                'otherValue' => 1337,
            ],
        ], [], $this->resolveInfoMock());
    }
}
