<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\InputMakeCommand;

class InputMakeCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->setMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->callback(function (string $path): bool {
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Inputs/Example.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class Example extends InputType/', $contents);
                    $this->assertRegExp("/'name' => 'Example',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InputMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'Example',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Input created successfully/', $tester->getDisplay());
    }

    public function testNameEndsWithInput(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->setMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->callback(function (string $path): bool {
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Inputs/ExampleInput.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleInput extends InputType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleInput',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InputMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleInput',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Input created successfully/', $tester->getDisplay());
    }

    public function testNameEndsWithInputObject(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->setMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->callback(function (string $path): bool {
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Inputs/ExampleInputObject.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleInputObject extends InputType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleInput',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InputMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleInputObject',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Input created successfully/', $tester->getDisplay());
    }

    public function testNameEndsWithType(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->setMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->callback(function (string $path): bool {
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Inputs/ExampleInputObjectType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleInputObjectType extends InputType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleInput',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InputMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleInputObjectType',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Input created successfully/', $tester->getDisplay());
    }
}
