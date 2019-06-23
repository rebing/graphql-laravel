<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\QueryMakeCommand;

class QueryMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel/app/GraphQL/Query/ExampleQuery.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleQuery extends Query/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleQuery',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(QueryMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleQuery',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Query created successfully/', $tester->getDisplay());
    }
}
