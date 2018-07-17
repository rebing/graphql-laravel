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
        $this->assertEquals($rules['test'], ['required']);
        $this->assertEquals($rules['test_with_rules'], ['required']);
        $this->assertEquals($rules['test_with_rules_closure'], ['required']);
        $this->assertEquals($rules['test_with_rules_input_object'], ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_input_object.val'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_input_object.nest'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_input_object.nest.email'), ['email']);
        $this->assertEquals(array_get($rules, 'test_with_rules_input_object.list'), ['required']);
        $this->assertEquals(array_get($rules, 'test_with_rules_input_object.list.*.email'), ['email']);
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
            'test' => 'test',
            'test_with_rules' => 'test',
            'test_with_rules_closure' => 'test',
            'test_with_rules_input_object' => [
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
            $this->assertTrue($messages->has('test'));
            $this->assertTrue($messages->has('test_with_rules'));
            $this->assertTrue($messages->has('test_with_rules_closure'));
            $this->assertTrue($messages->has('test_with_rules_input_object.val'));
            $this->assertTrue($messages->has('test_with_rules_input_object.nest'));
            $this->assertTrue($messages->has('test_with_rules_input_object.list'));
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
        $rules = $field->getRules();
        $attributes = $field->getAttributes();
        try {
            $attributes['resolve'](null, [
                 'test_with_rules_input_object' => [
                     'nest' => ['email' => 'invalidTestEmail.com'],
                 ],
             ], [], null);
        } catch (\Rebing\GraphQL\Error\ValidationError $e) {
            $messages = $e->getValidatorMessages();

            $this->assertEquals($messages->first('test'), 'The test field is required.');
            $this->assertEquals($messages->first('test_with_rules_input_object.nest.email'), 'The test with rules input object.nest.email must be a valid email address.');
        }
    }
}
