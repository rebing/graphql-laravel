<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateExampleMutationForRuleTesting extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'updateExampleMutationForRuleTesting',
    ];

    public function type(): Type
    {
        return GraphQL::type('Example');
    }

    /**
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    protected function rules(array $args = []): array
    {
        return [
            'test' => ['required'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function args(): array
    {
        return [
            'test_with_rules_closure' => [
                'name' => 'test',
                'type' => Type::string(),
                'rules' => function () {
                    return ['required'];
                },
            ],
            'test_with_rules_callback_params' => [
                'type' => GraphQL::type('ExampleRuleTestingInputObject'),
                'rules' => function ($inputArguments, $mutationArguments) {
                    $fullParamsExpected = [
                        'test_with_rules_callback_params' => [
                            'otherValue' => 1337,
                        ],
                    ];

                    Assert::assertSame(
                        $mutationArguments,
                        $fullParamsExpected
                    );

                    Assert::assertSame(
                        $inputArguments,
                        $fullParamsExpected
                    );

                    return ['required'];
                },
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function resolve($root, array $args): array
    {
        return [
            'test' => $args['test'],
        ];
    }
}
