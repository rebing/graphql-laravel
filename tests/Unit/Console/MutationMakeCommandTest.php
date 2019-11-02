<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\MutationMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class MutationMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    /**
     * @dataProvider dataForMakeCommand
     * @param  string  $inputName
     * @param  string  $expectedFilename
     * @param  string  $expectedClassDefinition
     * @param  string  $expectedGraphqlName
     */
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition,
        string $expectedGraphqlName
    ): void {
        $this->assertMakeCommand(
            'Mutation',
            MutationMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Mutations',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Mutations/Example.php',
                'expectedClassDefinition' => 'Example extends Mutation',
                'expectedGraphqlName' => "'name' => 'example',",
            ],
            'ExampleMutation' => [
                'inputName' => 'ExampleMutation',
                'expectedFilename' => 'GraphQL/Mutations/ExampleMutation.php',
                'expectedClassDefinition' => 'ExampleMutation extends Mutation',
                'expectedGraphqlName' => "'name' => 'example',",
            ],
        ];
    }
}
