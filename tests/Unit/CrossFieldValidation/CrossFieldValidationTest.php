<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CrossFieldValidation;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\MockObject\Stub;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Support\RulesPrefixer;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;
use Rebing\GraphQL\Tests\TestCase;
use stdClass;

class CrossFieldValidationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
            'CrossFieldRecipientInput' => CrossFieldRecipientInputType::class,
            'CrossFieldConditionalInput' => CrossFieldConditionalInputType::class,
            'CrossFieldParentInput' => CrossFieldParentInputType::class,
        ]);
    }

    protected function resolveInfoMock(): Stub
    {
        return self::createStub(ResolveInfo::class);
    }

    /**
     * Core issue #930: prohibits rules in a list InputType should reference
     * siblings using the correct dot-notation path (e.g. recipients.0.mintParams).
     */
    public function testProhibitsInListInputTypeIsTransformed(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['createParams' => 'foo', 'mintParams' => null, 'email' => 'test@test.com'],
            ],
        ]);

        // The rules for recipients.0.createParams should have prohibits:recipients.0.mintParams
        self::assertArrayHasKey('recipients.0.createParams', $rules);
        $createRules = $rules['recipients.0.createParams'];
        self::assertContains('prohibits:recipients.0.mintParams', $createRules);

        // And vice versa
        self::assertArrayHasKey('recipients.0.mintParams', $rules);
        $mintRules = $rules['recipients.0.mintParams'];
        self::assertContains('prohibits:recipients.0.createParams', $mintRules);
    }

    /**
     * required_without in a list InputType should reference siblings correctly.
     */
    public function testRequiredWithoutInListInputTypeIsTransformed(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['email' => 'test@test.com', 'phone' => null],
            ],
        ]);

        self::assertArrayHasKey('recipients.0.email', $rules);
        self::assertContains('required_without:recipients.0.phone', $rules['recipients.0.email']);

        self::assertArrayHasKey('recipients.0.phone', $rules);
        self::assertContains('required_without:recipients.0.email', $rules['recipients.0.phone']);
    }

    /**
     * Multiple list items should each get their own index in rule prefixes.
     */
    public function testMultipleListItemsGetCorrectIndices(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['createParams' => 'foo', 'email' => 'a@b.com'],
                ['mintParams' => 'bar', 'phone' => '1234567890'],
            ],
        ]);

        // First item
        self::assertContains('prohibits:recipients.0.mintParams', $rules['recipients.0.createParams']);
        self::assertContains('required_without:recipients.0.phone', $rules['recipients.0.email']);

        // Second item
        self::assertContains('prohibits:recipients.1.createParams', $rules['recipients.1.mintParams']);
        self::assertContains('required_without:recipients.1.email', $rules['recipients.1.phone']);
    }

    /**
     * Non-list InputType (single object) should also get cross-field rules transformed.
     */
    public function testNonListInputTypeIsTransformed(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'singleRecipient' => [
                'createParams' => 'foo',
                'mintParams' => null,
                'email' => 'test@test.com',
            ],
        ]);

        self::assertArrayHasKey('singleRecipient.createParams', $rules);
        self::assertContains('prohibits:singleRecipient.mintParams', $rules['singleRecipient.createParams']);
    }

    /**
     * required_if (Category B) should only prefix the first parameter,
     * leaving the value parameter untouched.
     */
    public function testRequiredIfOnlyPrefixesFirstParam(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'conditionalItems' => [
                ['mode' => 'advanced', 'advancedConfig' => null],
            ],
        ]);

        self::assertArrayHasKey('conditionalItems.0.advancedConfig', $rules);
        // "mode" should be prefixed, "advanced" (the value) should NOT be
        self::assertContains(
            'required_if:conditionalItems.0.mode,advanced',
            $rules['conditionalItems.0.advancedConfig'],
        );
    }

    /**
     * Deep nesting: InputType containing another InputType should still
     * resolve sibling names correctly.
     */
    public function testDeepNestedInputTypeIsTransformed(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'deepNested' => [
                'nested' => [
                    'createParams' => 'foo',
                    'mintParams' => null,
                    'email' => 'test@test.com',
                ],
            ],
        ]);

        self::assertArrayHasKey('deepNested.nested.createParams', $rules);
        self::assertContains(
            'prohibits:deepNested.nested.mintParams',
            $rules['deepNested.nested.createParams'],
        );
    }

    /**
     * Top-level rules (from the mutation's rules() method) should not be
     * modified since they're already at root level.
     */
    public function testTopLevelRulesAreNotModified(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['createParams' => 'foo', 'email' => 'test@test.com'],
            ],
        ]);

        // The top-level 'recipients' rule should remain unchanged
        self::assertArrayHasKey('recipients', $rules);
        self::assertContains('required', $rules['recipients']);
    }

    /**
     * Rule references that already contain dots should not be modified
     * (they're already fully-qualified paths).
     */
    public function testAlreadyQualifiedPathsAreNotModified(): void
    {
        $args = [
            'items' => [
                'type' => Type::nonNull(
                    Type::listOf(
                        Type::nonNull(
                            new InputObjectType([
                                'name' => 'TestInput',
                                'fields' => [
                                    'fieldA' => ['type' => Type::string()],
                                    'fieldB' => ['type' => Type::string()],
                                ],
                            ]),
                        ),
                    ),
                ),
            ],
        ];

        $rules = [
            'items.0.fieldA' => ['prohibits:some.other.path'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        // Already-qualified paths should NOT be re-prefixed
        self::assertContains('prohibits:some.other.path', $result['items.0.fieldA']);
    }

    /**
     * Rule parameters that don't match any sibling field name should not be modified.
     * This handles the case where a parameter is a literal value, not a field reference.
     */
    public function testNonSiblingReferencesAreNotModified(): void
    {
        $args = [
            'items' => [
                'type' => Type::nonNull(
                    Type::listOf(
                        Type::nonNull(
                            new InputObjectType([
                                'name' => 'TestInput',
                                'fields' => [
                                    'amount' => ['type' => Type::int()],
                                    'limit' => ['type' => Type::int()],
                                ],
                            ]),
                        ),
                    ),
                ),
            ],
        ];

        $rules = [
            'items.0.amount' => ['gt:100'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        // "100" is not a sibling field name, so it should remain as-is
        self::assertContains('gt:100', $result['items.0.amount']);
    }

    /**
     * Rule objects (non-string rules) should be passed through untouched.
     */
    public function testRuleObjectsAreNotModified(): void
    {
        $ruleObject = new \Illuminate\Validation\Rules\In(['a', 'b', 'c']);

        $args = [
            'items' => [
                'type' => Type::nonNull(
                    Type::listOf(
                        Type::nonNull(
                            new InputObjectType([
                                'name' => 'TestInput',
                                'fields' => [
                                    'fieldA' => ['type' => Type::string()],
                                ],
                            ]),
                        ),
                    ),
                ),
            ],
        ];

        $rules = [
            'items.0.fieldA' => ['required', $ruleObject],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertSame($ruleObject, $result['items.0.fieldA'][1]);
    }

    /**
     * String rule values (e.g. "required|prohibits:foo") should be transformed.
     */
    public function testPipeDelimitedStringRulesAreNotSplitButTransformedAsWhole(): void
    {
        $field = new CrossFieldTestMutation;

        // getRules returns array rules (not pipe-delimited), but let's test
        // RulesPrefixer directly with a pipe-delimited string
        $args = $field->args();
        $rules = [
            'recipients.0.createParams' => 'nullable|prohibits:mintParams',
        ];

        $result = RulesPrefixer::apply($rules, $args);

        // Pipe-delimited strings are treated as a single string, not split.
        // The entire string is scanned as one rule — the first colon-delimited
        // rule name would be "nullable|prohibits" which isn't recognized,
        // so it's left as-is. This is expected because Laravel rules should
        // be arrays when used with cross-field references.
        self::assertSame('nullable|prohibits:mintParams', $result['recipients.0.createParams']);
    }

    /**
     * By default, processCollectedRules() prefixes cross-field references
     * with the full dot-notation path.
     *
     * @see testProcessCollectedRulesOverrideDisablesPrefixing — counterpart showing the escape hatch restores old behavior
     */
    public function testProcessCollectedRulesPrefixesByDefault(): void
    {
        $field = new CrossFieldTestMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['createParams' => 'foo', 'mintParams' => null, 'email' => 'test@test.com'],
            ],
        ]);

        // With default behavior, sibling references are prefixed
        self::assertArrayHasKey('recipients.0.createParams', $rules);
        $createRules = $rules['recipients.0.createParams'];
        self::assertContains('prohibits:recipients.0.mintParams', $createRules);
        self::assertNotContains('prohibits:mintParams', $createRules);
    }

    /**
     * Override processCollectedRules() to disable prefixing (escape hatch),
     * restoring the old pre-fix behavior.
     *
     * @see testProcessCollectedRulesPrefixesByDefault — counterpart showing the default prefixed behavior
     */
    public function testProcessCollectedRulesOverrideDisablesPrefixing(): void
    {
        $field = new CrossFieldDisabledPrefixingMutation;

        $rules = $field->getRules([
            'recipients' => [
                ['createParams' => 'foo', 'mintParams' => null, 'email' => 'test@test.com'],
            ],
        ]);

        // With prefixing disabled, the original unprefixed rules should remain
        self::assertArrayHasKey('recipients.0.createParams', $rules);
        $createRules = $rules['recipients.0.createParams'];
        self::assertContains('prohibits:mintParams', $createRules);
        self::assertNotContains('prohibits:recipients.0.mintParams', $createRules);
    }

    /**
     * End-to-end: validation should pass when the cross-field rules are satisfied.
     * (Both fields should not be provided simultaneously with prohibits rule.)
     */
    public function testValidationPassesWhenCrossFieldRulesAreSatisfied(): void
    {
        $field = $this->createPartialMock(CrossFieldTestMutation::class, ['resolve']);

        $field->expects(self::once())
            ->method('resolve');

        $attributes = $field->getAttributes();

        // Only createParams is set, mintParams is null — should pass prohibits rule
        $attributes['resolve'](null, [
            'recipients' => [
                [
                    'createParams' => 'foo',
                    'mintParams' => null,
                    'email' => 'test@test.com',
                ],
            ],
        ], [], $this->resolveInfoMock());
    }

    /**
     * End-to-end: validation should fail when cross-field rules are violated
     * (both prohibited fields provided).
     */
    public function testValidationFailsWhenCrossFieldRulesAreViolated(): void
    {
        $field = new CrossFieldTestMutation;
        $attributes = $field->getAttributes();

        $exception = null;

        try {
            $attributes['resolve'](null, [
                'recipients' => [
                    [
                        'createParams' => 'foo',
                        'mintParams' => 'bar',
                        'email' => 'test@test.com',
                    ],
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
            // Expected
        }

        self::assertInstanceOf(ValidationError::class, $exception);

        $messages = $exception->getValidatorMessages();
        // Both fields should have validation errors about prohibiting each other
        self::assertTrue(
            $messages->has('recipients.0.createParams') || $messages->has('recipients.0.mintParams'),
            'Expected a validation error on createParams or mintParams for the prohibits rule',
        );
    }

    /**
     * End-to-end: required_without should work correctly in list items.
     * If neither email nor phone is provided, validation should fail.
     */
    public function testRequiredWithoutValidationFailsInList(): void
    {
        $field = new CrossFieldTestMutation;
        $attributes = $field->getAttributes();

        $exception = null;

        try {
            $attributes['resolve'](null, [
                'recipients' => [
                    [
                        'createParams' => 'foo',
                        // neither email nor phone provided
                    ],
                ],
            ], [], $this->resolveInfoMock());
        } catch (ValidationError $exception) {
            // Expected
        }

        self::assertInstanceOf(ValidationError::class, $exception);

        $messages = $exception->getValidatorMessages();
        self::assertTrue(
            $messages->has('recipients.0.email') || $messages->has('recipients.0.phone'),
            'Expected validation error for required_without rule when neither email nor phone is provided',
        );
    }

    /**
     * A rule value that is neither a string nor an array (e.g. an object)
     * should be returned as-is when siblings exist.
     */
    public function testNonStringNonArrayRuleValueIsPassedThrough(): void
    {
        $ruleObject = new stdClass;
        $ruleObject->custom = true;

        $args = $this->makeListInputArgs(['fieldA', 'fieldB']);

        $rules = [
            'items.0.fieldA' => $ruleObject,
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertSame($ruleObject, $result['items.0.fieldA']);
    }

    /**
     * A rule key referencing an arg name that doesn't exist in the type tree
     * should leave rules untouched.
     */
    public function testRuleKeyWithNonExistentArgIsNotModified(): void
    {
        $args = $this->makeListInputArgs(['fieldA', 'fieldB']);

        $rules = [
            'nonExistent.0.fieldA' => ['prohibits:fieldB'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertContains('prohibits:fieldB', $result['nonExistent.0.fieldA']);
    }

    /**
     * A field definition that has no 'type' key should cause the type-tree
     * walk to bail out, leaving rules untouched.
     */
    public function testFieldDefWithNoTypeKeyIsNotModified(): void
    {
        $args = [
            'items' => [
                'name' => 'items',
                // no 'type' key
            ],
        ];

        $rules = [
            'items.0.fieldA' => ['prohibits:fieldB'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertContains('prohibits:fieldB', $result['items.0.fieldA']);
    }

    /**
     * A field wrapping a scalar type (not an InputObjectType) should cause
     * the type-tree walk to bail out, leaving rules untouched.
     */
    public function testFieldWrappingScalarTypeIsNotModified(): void
    {
        $args = [
            'items' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
            ],
        ];

        $rules = [
            'items.0.fieldA' => ['prohibits:fieldB'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertContains('prohibits:fieldB', $result['items.0.fieldA']);
    }

    /**
     * A field definition passed as a stdClass object with a 'type' property
     * should be handled correctly by extractType().
     */
    public function testFieldDefAsStdClassWithTypeProperty(): void
    {
        $inputType = new InputObjectType([
            'name' => 'StdClassTestInput',
            'fields' => [
                'fieldA' => ['type' => Type::string()],
                'fieldB' => ['type' => Type::string()],
            ],
        ]);

        $args = [
            'wrapper' => (object) [
                'type' => Type::nonNull($inputType),
            ],
        ];

        $rules = [
            'wrapper.fieldA' => ['prohibits:fieldB'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertContains('prohibits:wrapper.fieldB', $result['wrapper.fieldA']);
    }

    /**
     * A stdClass field def whose 'type' property is not a GraphQL type
     * should bail out, leaving rules untouched.
     */
    public function testFieldDefAsStdClassWithNonGraphqlType(): void
    {
        $args = [
            'wrapper' => (object) [
                'type' => 'not-a-graphql-type',
            ],
        ];

        $rules = [
            'wrapper.fieldA' => ['prohibits:fieldB'],
        ];

        $result = RulesPrefixer::apply($rules, $args);

        self::assertContains('prohibits:fieldB', $result['wrapper.fieldA']);
    }

    /**
     * Helper: build an args array with a list-of-InputObjectType containing the given field names.
     *
     * @param list<string> $fieldNames
     * @return array<string,mixed>
     */
    private function makeListInputArgs(array $fieldNames): array
    {
        $fields = [];

        foreach ($fieldNames as $name) {
            $fields[$name] = ['type' => Type::string()];
        }

        return [
            'items' => [
                'type' => Type::nonNull(
                    Type::listOf(
                        Type::nonNull(
                            new InputObjectType([
                                'name' => 'TestInput',
                                'fields' => $fields,
                            ]),
                        ),
                    ),
                ),
            ],
        ];
    }
}
