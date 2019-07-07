<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\TypeMakeCommand;
use Rebing\GraphQL\Console\InterfaceMakeCommand;

class InterfaceMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Interfaces/ExampleInterfaceType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleInterfaceType extends InterfaceType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleInterfaceType',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(InterfaceMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleInterfaceType',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Interface created successfully/', $tester->getDisplay());
    }
}
