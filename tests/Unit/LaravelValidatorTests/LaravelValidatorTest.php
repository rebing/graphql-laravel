<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\LaravelValidatorTests;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Validation\Validator;

class LaravelValidatorTest extends TestCase
{
    public function testInPassRulePass(): void
    {
        $rules = [
            'arg' => [
                'in:valid_name',
                new RuleObjectPass(),
            ],
        ];

        $data = [
            'arg' => 'valid_name',
        ];

        /** @var Validator $validator */
        $validator = \Validator::make($data, $rules);

        $this->assertSame([], $validator->errors()->all());
    }

    public function testInPassRuleFail(): void
    {
        $rules = [
            'arg' => [
                'in:valid_name',
                new RuleObjectFail(),
            ],
        ];

        $data = [
            'arg' => 'valid_name',
        ];

        /** @var Validator $validator */
        $validator = \Validator::make($data, $rules);

        $expectedMessages = [
            'rule object validation fails',
        ];
        $this->assertSame($expectedMessages, $validator->errors()->all());
    }

    public function testInFailRulePass(): void
    {
        $rules = [
            'arg' => [
                'in:valid_name',
                new RuleObjectPass(),
            ],
        ];

        $data = [
            'arg' => 'invalid_name',
        ];

        /** @var Validator $validator */
        $validator = \Validator::make($data, $rules);

        $expectedMessages = [
            'The selected arg is invalid.',
        ];
        $this->assertSame($expectedMessages, $validator->errors()->all());
    }

    public function testInFailRuleFail(): void
    {
        $rules = [
            'arg' => [
                'in:valid_name',
                new RuleObjectFail(),
            ],
        ];

        $data = [
            'The selected arg is invalid.',
            'rule object validation fails',
        ];

        /** @var Validator $validator */
        $validator = \Validator::make($data, $rules);

        $this->assertSame([], $validator->errors()->all());
    }
}
