<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Console\InputMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;

class InputMakeCommandTest extends TestCase
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
            'Input',
            InputMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Inputs',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Inputs/Example.php',
                'expectedClassDefinition' => 'Example extends InputType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleInput' => [
                'inputName' => 'ExampleInput',
                'expectedFilename' => 'GraphQL/Inputs/ExampleInput.php',
                'expectedClassDefinition' => 'ExampleInput extends InputType',
                'expectedGraphqlName' => "'name' => 'ExampleInput',",
            ],
            'ExampleInputObject' => [
                'inputName' => 'ExampleInputObject',
                'expectedFilename' => 'GraphQL/Inputs/ExampleInputObject.php',
                'expectedClassDefinition' => 'ExampleInputObject extends InputType',
                'expectedGraphqlName' => "'name' => 'ExampleInput',",
            ],
            'ExampleInputObjectType' => [
                'inputName' => 'ExampleInputObjectType',
                'expectedFilename' => 'GraphQL/Inputs/ExampleInputObjectType.php',
                'expectedClassDefinition' => 'ExampleInputObjectType extends InputType',
                'expectedGraphqlName' => "'name' => 'ExampleInput',",
            ],
        ];
    }
}
