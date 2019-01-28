<?php

use Illuminate\Validation\Validator;

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
     *
     * @test
     */
    public function testGetRules()
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $rules = $field->getRules();

        $this->assertInternalType('array', $rules);
        $this->assertArrayHasKey('test', $rules);
        $this->assertArrayHasKey('test_with_rules', $rules);
        $this->assertArrayHasKey('test_with_rules_closure', $rules);
        $this->assertEquals($rules['test'], ['email']);
        $this->assertEquals($rules['test_with_rules'], ['email']);
        $this->assertEquals($rules['test_with_rules_closure'], ['email']);
        $this->assertEquals($rules['test_with_rules_nullable_input_object'], ['nullable']);
        $this->assertNull(array_get($rules, 'test_with_rules_nullable_input_object.val'));
        $this->assertNull(array_get($rules, 'test_with_rules_nullable_input_object.nest'));
        $this->assertNull(array_get($rules, 'test_with_rules_nullable_input_object.nest.email'));
        $this->assertNull(array_get($rules, 'test_with_rules_nullable_input_object.list'));
        $this->assertNull(array_get($rules, 'test_with_rules_nullable_input_object.list.*.email'));
        $this->assertEquals($rules['test_with_rules_non_nullable_input_object'], ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_non_nullable_input_object.val'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_non_nullable_input_object.nest'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_non_nullable_input_object.nest.email'), ['email']);
        $this->assertEquals(array_get($rules, 'test_with_rules_non_nullable_input_object.list'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_non_nullable_input_object.list.*.email'), ['email']);
    }

    /**
     * Test resolve.
     *
     * @test
     */
    public function testResolve()
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
                    ->setMethods(['resolve'])
                    ->getMock();

        $field->expects($this->once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [
            'test' => 'test@test.com',
            'test_with_rules' => 'test@test.com',
            'test_with_rules_closure' => 'test@test.com',
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
     *
     * @test
     * @expectedException \Rebing\GraphQL\Error\ValidationError
     */
    public function testResolveThrowValidationError()
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [], [], null);
    }

    /**
     * Test validation error.
     *
     * @test
     */
    public function testValidationError()
    {
        $class = $this->getFieldClass();
        $field = new $class();

        $attributes = $field->getAttributes();

        try {
            $attributes['resolve'](null, [], [], null);
        } catch (\Rebing\GraphQL\Error\ValidationError $e) {
            // FIXME Replace with getValidator()
            $validator = $e->validator;

            $this->assertInstanceOf(Validator::class, $validator);

            $messages = $e->getValidatorMessages();
            $this->assertFalse($messages->has('test'));
            $this->assertFalse($messages->has('test_with_rules'));
            $this->assertFalse($messages->has('test_with_rules_closure'));
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
     *
     * @test
     */
    public function testCustomValidationErrorMessages()
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();
        try {
            $attributes['resolve'](null, [
                'test' => 'invalidTestEmail.com',
                'test_with_rules' => 'invalidTestEmail.com',
                'test_with_rules_closure' => 'invalidTestEmail.com',
                'test_with_rules_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
                'test_with_rules_non_nullable_input_object' => [
                    'nest' => ['email' => 'invalidTestEmail.com'],
                ],
             ], [], null);
        } catch (\Rebing\GraphQL\Error\ValidationError $e) {
            $messages = $e->getValidatorMessages();

            $this->assertEquals($messages->first('test'), 'The test must be a valid email address.');
            $this->assertEquals($messages->first('test_with_rules'), 'The test with rules must be a valid email address.');
            $this->assertEquals($messages->first('test_with_rules_closure'), 'The test with rules closure must be a valid email address.');
            $this->assertEquals($messages->first('test_with_rules_nullable_input_object.nest.email'), 'The test with rules nullable input object.nest.email must be a valid email address.');
            $this->assertEquals($messages->first('test_with_rules_non_nullable_input_object.nest.email'), 'The test with rules non nullable input object.nest.email must be a valid email address.');
        }
    }
}
