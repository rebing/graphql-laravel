<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\TypeMakeCommand;

class TypeMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel[/\\\\]app/GraphQL/Types/ExampleType.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleType extends GraphQLType/', $contents);
                    $this->assertRegExp("/'name' => 'ExampleType',/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(TypeMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleType',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Type created successfully/', $tester->getDisplay());
    }
}
