<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationValidationInWithCustomRulesTests;

use Illuminate\Contracts\Support\MessageBag;
use Rebing\GraphQL\Tests\TestCase;

class MutationValidationInWithCustomRulesTest extends TestCase
{
    public function testInPassRulePass(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_pass: $arg_in_rule_pass)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'arg_in_rule_pass' => 'valid_name',
        ]);

        $expectedResult = [
            'data' => [
                'mutationWithCustomRuleWithRuleObject' => 'mutation result',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    public function testInPassRuleFail(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_fail: $arg_in_rule_fail)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_fail' => 'valid_name',
            ],
        ]);

        self::assertCount(1, $result['errors']);
        self::assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'rule object validation fails',
        ];
        self::assertSame($expectedMessages, $messageBag->all());
    }

    public function testInFailRulePass(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_pass: $arg_in_rule_pass)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_pass' => 'invalid_name',
            ],
        ]);

        self::assertCount(1, $result['errors']);
        self::assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The selected arg in rule pass is invalid.',
        ];
        self::assertSame($expectedMessages, $messageBag->all());
    }

    public function testInFailRuleFail(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_fail: $arg_in_rule_fail)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_fail' => 'invalid_name',
            ],
        ]);

        self::assertCount(1, $result['errors']);
        self::assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The selected arg in rule fail is invalid.',
            'rule object validation fails',
        ];
        self::assertSame($expectedMessages, $messageBag->all());
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                MutationWithCustomRuleWithRuleObject::class,
            ],
        ]);
    }
}
