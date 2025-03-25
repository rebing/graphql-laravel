<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Console\ExecutionMiddlewareMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class ExecutionMiddlewareMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    #[DataProvider('dataForMakeCommand')]
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition
    ): void {
        $this->assertMakeCommand(
            'ExecutionMiddleware',
            ExecutionMiddlewareMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Middleware\\\\Execution',
            $expectedClassDefinition
        );
    }

    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Middleware/Execution/Example.php',
                'expectedClassDefinition' => 'Example extends AbstractExecutionMiddleware',
            ],
            'ExampleMiddleware' => [
                'inputName' => 'ExampleMiddleware',
                'expectedFilename' => 'GraphQL/Middleware/Execution/ExampleMiddleware.php',
                'expectedClassDefinition' => 'ExampleMiddleware extends AbstractExecutionMiddleware',
            ],
        ];
    }
}
