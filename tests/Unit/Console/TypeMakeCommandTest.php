<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\TypeMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class TypeMakeCommandTest extends TestCase
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
            'Type',
            TypeMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Types',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Types/Example.php',
                'expectedClassDefinition' => 'Example extends GraphQLType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleType' => [
                'inputName' => 'ExampleType',
                'expectedFilename' => 'GraphQL/Types/ExampleType.php',
                'expectedClassDefinition' => 'ExampleType extends GraphQLType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
        ];
    }
}
