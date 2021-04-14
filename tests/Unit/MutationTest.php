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
                    ->setMethods(['resolve'])
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
        self::assertCount(6, $messages->all());
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

            self::assertInstanceOf(Validator::class, $validator);

            $messages = $e->getValidatorMessages();

            self::assertTrue($messages->has('test'));
            self::assertTrue($messages->has('test_with_rules'));
            self::assertTrue($messages->has('test_with_rules_closure'));
            self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
            self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
            self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
            self::assertCount(6, $messages->all());
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

        self::assertInstanceOf(Validator::class, $validator);

        $messages = $exception->getValidatorMessages();

        self::assertTrue($messages->has('test'));
        self::assertTrue($messages->has('test_with_rules'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object'));
        self::assertTrue($messages->has('test_with_rules_closure'));
        self::assertCount(4, $messages->all());
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
        self::assertCount(3, $messages->all());
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

        self::assertTrue($messages->has('test_with_rules_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.val'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_nullable_input_object.list'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.otherValue'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.val'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.nest'));
        self::assertTrue($messages->has('test_with_rules_non_nullable_input_object.list'));
        self::assertCount(12, $messages->all());
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
        self::assertCount(4, $messages->all());
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
        self::assertCount(7, $messages->all());
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

        self::assertEquals($messages->first('test'), 'The test field is required.');
        self::assertEquals($messages->first('test_with_rules_nullable_input_object.nest.email'), 'The test with rules nullable input object.nest.email must be a valid email address.');
        self::assertEquals($messages->first('test_with_rules_non_nullable_input_object.nest.email'), 'The test with rules non nullable input object.nest.email must be a valid email address.');
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
