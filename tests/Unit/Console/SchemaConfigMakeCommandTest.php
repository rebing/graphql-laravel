<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Console\SchemaConfigMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class SchemaConfigMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    #[DataProvider('dataForMakeCommand')]
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition
    ): void {
        $this->assertMakeCommand(
            'Schema',
            SchemaConfigMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Schemas',
            $expectedClassDefinition
        );
    }

    /**
     * @return array<string,mixed>
     */
    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Schemas/Example.php',
                'expectedClassDefinition' => 'Example implements ConfigConvertible',
            ],
            'ExampleSchema' => [
                'inputName' => 'ExampleSchema',
                'expectedFilename' => 'GraphQL/Schemas/ExampleSchema.php',
                'expectedClassDefinition' => 'ExampleSchema implements ConfigConvertible',
            ],
        ];
    }
}
