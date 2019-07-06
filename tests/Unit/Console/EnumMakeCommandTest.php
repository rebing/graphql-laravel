<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\EnumMakeCommand;

class EnumMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Enums/ExampleEnum.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleEnum extends EnumType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleEnum',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(EnumMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleEnum',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Enum created successfully/', $tester->getDisplay());
    }
}
