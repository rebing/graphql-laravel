<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class ExampleRuleTestingInputObject extends InputType
{
    /**
     * @var array<string,string>
     */
    protected $attributes = [
        'name' => 'ExampleRuleTestingInputObject',
    ];

    public function type(): ListOfType
    {
        return Type::listOf(Type::string());
    }

    public function fields(): array
    {
        return [
            'val' => [
                'type' => Type::int(),
                'rules' => ['required'],
            ],
            'otherValue' => [
                'type' => Type::int(),
                'rules' => function ($arguments) {
                    Assert::assertSame(
                        $arguments,
                        ['otherValue' => 1337]
                    );

                    return ['required'];
                },
            ],
            'nest' => [
                'name' => 'nest',
                'type' => GraphQL::type('ExampleNestedValidationInputObject'),
                'rules' => ['required'],
            ],
            'list' => [
                'name' => 'list',
                'type' => Type::listOf(GraphQL::type('ExampleNestedValidationInputObject')),
                'rules' => ['required'],
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @return array<string>
     */
    public function resolve($root, array $args): array
    {
        return ['test'];
    }
}
