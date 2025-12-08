<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Console\ScalarMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class ScalarMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    #[DataProvider('dataForMakeCommand')]
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition,
        string $expectedGraphqlName,
    ): void {
        $this->assertMakeCommand(
            'Scalar',
            ScalarMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Scalars',
            $expectedClassDefinition,
            $expectedGraphqlName,
        );
    }

    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Scalars/Example.php',
                'expectedClassDefinition' => 'Example extends ScalarType implements TypeConvertible',
                'expectedGraphqlName' => 'public string \\$name = \'Example\';',
            ],
            'ExampleScalar' => [
                'inputName' => 'ExampleScalar',
                'expectedFilename' => 'GraphQL/Scalars/ExampleScalar.php',
                'expectedClassDefinition' => 'ExampleScalar extends ScalarType implements TypeConvertible',
                'expectedGraphqlName' => 'public string \\$name = \'ExampleScalar\';',
            ],
        ];
    }
}
