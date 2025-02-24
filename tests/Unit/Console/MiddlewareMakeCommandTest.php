<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Console\MiddlewareMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class MiddlewareMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    #[DataProvider('dataForMakeCommand')]
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition
    ): void {
        $this->assertMakeCommand(
            'Middleware',
            MiddlewareMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Middleware',
            $expectedClassDefinition
        );
    }

    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Middleware/Example.php',
                'expectedClassDefinition' => 'Example extends Middleware',
            ],
            'ExampleMiddleware' => [
                'inputName' => 'ExampleMiddleware',
                'expectedFilename' => 'GraphQL/Middleware/ExampleMiddleware.php',
                'expectedClassDefinition' => 'ExampleMiddleware extends Middleware',
            ],
        ];
    }
}
