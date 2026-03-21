<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Console\InputMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class InputMakeCommandTest extends TestCase
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
            'Input',
            InputMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Inputs',
            $expectedClassDefinition,
            $expectedGraphqlName,
        );
    }

    public function testCommandWithOneofOption(): void
    {
        $filesystemMock = $this->createPartialMock(Filesystem::class, [
            'isDirectory',
            'makeDirectory',
            'put',
        ]);
        $filesystemMock
            ->expects(self::once())
            ->method('put')
            ->with(
                self::callback(function (string $path): bool {
                    $this->assertMatchesRegularExpression('|laravel[/\\\\]app/GraphQL/Inputs/Example.php|', $path);

                    return true;
                }),
                self::callback(function (string $contents): bool {
                    $this->assertMatchesRegularExpression('/class Example extends InputType/', $contents);
                    $this->assertStringContainsString("'isOneOf' => true", $contents);
                    $this->assertStringNotContainsString("'isOneOf' => false", $contents);

                    return true;
                }),
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InputMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'Example',
            '--oneof' => true,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertMatchesRegularExpression('/Input.*created successfully/', $tester->getDisplay());
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function dataForMakeCommand(): array
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
