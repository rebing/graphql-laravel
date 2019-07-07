<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\UnionMakeCommand;

class UnionMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Unions/Example.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class Example extends UnionType/', $contents);
                    $this->assertRegExp("/'name' => 'Example',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(UnionMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'Example',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Union created successfully/', $tester->getDisplay());
    }

    public function testEndsWithUnion(): void
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Unions/ExampleUnion.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleUnion extends UnionType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleUnion',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(UnionMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleUnion',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Union created successfully/', $tester->getDisplay());
    }

    public function testEndsWithType(): void
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Unions/ExampleType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleType extends UnionType/', $contents);
                    $this->assertRegExp("/'name' => 'Example',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(UnionMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleType',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Union created successfully/', $tester->getDisplay());
    }

    public function testEndsWithUnionType(): void
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Unions/ExampleUnionType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleUnionType extends UnionType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleUnion',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(UnionMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleUnionType',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Union created successfully/', $tester->getDisplay());
    }
}
