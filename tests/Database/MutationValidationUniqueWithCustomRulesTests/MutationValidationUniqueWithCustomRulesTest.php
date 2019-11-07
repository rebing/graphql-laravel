<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\MutationValidationUniqueWithCustomRulesTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Illuminate\Contracts\Support\MessageBag;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class MutationValidationUniqueWithCustomRulesTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testUniquePassRulePass(): void
    {
        /* @var User $user */
        factory(User::class)
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_pass: $arg_unique_rule_pass)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_pass' => 'another_name',
            ],
        ]);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL
        );

        $expectedResult = [
            'data' => [
                'mutationWithCustomRuleWithRuleObject' => 'mutation result',
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testUniqueFailRulePass(): void
    {
        /* @var User $user */
        factory(User::class)
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_pass: $arg_unique_rule_pass)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_pass' => 'name_unique',
            ],
        ]);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL
        );

        $this->assertCount(1, $result['errors']);
        $this->assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The arg unique rule pass has already been taken.',
        ];
        $this->assertSame($expectedMessages, $messageBag->all());
    }

    public function testUniquePassRuleFail(): void
    {
        /* @var User $user */
        factory(User::class)
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_fail: $arg_unique_rule_fail)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_fail' => 'another_name',
            ],
        ]);

        $this->assertCount(1, $result['errors']);
        $this->assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'rule object validation fails',
        ];
        $this->assertSame($expectedMessages, $messageBag->all());
    }

    public function testUniqueFailRuleFail(): void
    {
        /* @var User $user */
        factory(User::class)
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_fail: $arg_unique_rule_fail)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_fail' => 'name_unique',
            ],
        ]);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL
        );

        $this->assertCount(1, $result['errors']);
        $this->assertSame('validation', $result['errors'][0]['message']);
        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The arg unique rule fail has already been taken.',
            'rule object validation fails',
        ];
        $this->assertSame($expectedMessages, $messageBag->all());
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                MutationWithCustomRuleWithRuleObject::class,
            ],
        ]);
    }
}
