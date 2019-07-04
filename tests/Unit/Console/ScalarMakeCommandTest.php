<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\ScalarMakeCommand;

class ScalarMakeCommandTest extends TestCase
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
                    $this->assertRegExp('|laravel/app/GraphQL/Scalars/ExampleScalar.php|', $path);

                    return true;
                }),
                $this->callback(function (string $contents): bool {
                    $this->assertRegExp('/class ExampleScalar extends ScalarType implements TypeConvertible/', $contents);
                    $this->assertRegExp("/public \\\$name = 'ExampleScalar';/", $contents);

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(ScalarMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleScalar',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('/Scalar created successfully/', $tester->getDisplay());
    }
}
