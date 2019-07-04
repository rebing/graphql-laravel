<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\MutationMakeCommand;

class MutationMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Mutation/ExampleMutation.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleMutation extends Mutation/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleMutation',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(MutationMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleMutation',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Mutation created successfully/', $tester->getDisplay());
    }
}
