<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\ScalarMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class ScalarMakeCommandTest extends TestCase
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
            'Scalar',
            ScalarMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Scalars',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Scalars/Example.php',
                'expectedClassDefinition' => 'Example extends ScalarType implements TypeConvertible',
                'expectedGraphqlName' => '\$name = \'Example\';',
            ],
            'ExampleScalar' => [
                'inputName' => 'ExampleScalar',
                'expectedFilename' => 'GraphQL/Scalars/ExampleScalar.php',
                'expectedClassDefinition' => 'ExampleScalar extends ScalarType implements TypeConvertible',
                'expectedGraphqlName' => '\$name = \'ExampleScalar\';',
            ],
        ];
    }
}
