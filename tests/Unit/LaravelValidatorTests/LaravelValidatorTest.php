<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\LaravelValidatorTests;

use Illuminate\Support\Facades\Validator;
use Rebing\GraphQL\Tests\TestCase;

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

        $validator = Validator::make($data, $rules);

        self::assertSame([], $validator->errors()->all());
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

        $validator = Validator::make($data, $rules);

        $expectedMessages = [
            'rule object validation fails',
        ];
        self::assertSame($expectedMessages, $validator->errors()->all());
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

        $validator = Validator::make($data, $rules);

        $expectedMessages = [
            'The selected arg is invalid.',
        ];
        self::assertSame($expectedMessages, $validator->errors()->all());
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

        $validator = Validator::make($data, $rules);

        self::assertSame([], $validator->errors()->all());
    }
}
