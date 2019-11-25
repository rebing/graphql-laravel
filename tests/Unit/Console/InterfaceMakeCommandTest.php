<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\InterfaceMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class InterfaceMakeCommandTest extends TestCase
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
            'Interface',
            InterfaceMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Interfaces',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Interfaces/Example.php',
                'expectedClassDefinition' => 'Example extends InterfaceType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleType' => [
                'inputName' => 'ExampleType',
                'expectedFilename' => 'GraphQL/Interfaces/ExampleType.php',
                'expectedClassDefinition' => 'ExampleType extends InterfaceType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleInterface' => [
                'inputName' => 'ExampleInterface',
                'expectedFilename' => 'GraphQL/Interfaces/ExampleInterface.php',
                'expectedClassDefinition' => 'ExampleInterface extends InterfaceType',
                'expectedGraphqlName' => "'name' => 'ExampleInterface',",
            ],
            'ExampleInterfaceType' => [
                'inputName' => 'ExampleInterfaceType',
                'expectedFilename' => 'GraphQL/Interfaces/ExampleInterfaceType.php',
                'expectedClassDefinition' => 'ExampleInterfaceType extends InterfaceType',
                'expectedGraphqlName' => "'name' => 'ExampleInterface',",
            ],
        ];
    }
}
