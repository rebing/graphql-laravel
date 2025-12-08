<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\FieldMakeCommand;
use Rebing\GraphQL\Tests\TestCase;

class FieldMakeCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->onlyMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects(self::once())
            ->method('put')
            ->with(
                self::callback(function (string $path): bool {
                    $this->assertMatchesRegularExpression('|laravel[/\\\\]app/GraphQL/Fields/ExampleField.php|', $path);

                    return true;
                }),
                self::callback(function (string $contents): bool {
                    $this->assertMatchesRegularExpression('/class ExampleField extends Field/', $contents);
                    $this->assertMatchesRegularExpression("/'name' => 'ExampleField',/", $contents);

                    return true;
                }),
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(FieldMakeCommand::class);

        $tester = $this->runCommand($command, [
            'name' => 'ExampleField',
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertMatchesRegularExpression('/Field.*created successfully/', $tester->getDisplay());
    }
}
