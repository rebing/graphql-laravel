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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Unions/ExampleUnionType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleUnionType extends UnionType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleUnionType',/", $contents);

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
