<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Console\PublishCommand;
use Rebing\GraphQL\Tests\TestCase;

class PublishCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->setMethods([
                'copy',
                'isDirectory',
                'makeDirectory',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->at(2))
            ->method('copy')
            ->with(
                $this->callback(function (string $from): bool {
                    $this->assertRegExp('|/config/config.php|', $from, '1st call to copy, $from');

                    return true;
                }),
                $this->callback(function (string $to): bool {
                    $this->assertRegExp('|laravel[/\\\\]config/graphql.php|', $to, '1st call to copy, $to');

                    return true;
                })
            );
        $filesystemMock
            ->expects($this->at(5))
            ->method('copy')
            ->with(
                $this->callback(function (string $from): bool {
                    $this->assertRegExp('|/resources/views/graphiql.php|', $from, '2nd call to copy, $from');

                    return true;
                }),
                $this->callback(function (string $to): bool {
                    $this->assertRegExp('|laravel[/\\\\]resources/views/vendor/graphql/graphiql.php|', $to,
                        '2nd call to copy, $to');

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(PublishCommand::class);

        $tester = $this->runCommand($command);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRegExp('|Copied File.*[/\\\\]config[/\\\\]config.php.* To|', $tester->getDisplay());
        $this->assertRegExp('|Copied File.*[/\\\\]resources[/\\\\]views[/\\\\]graphiql.php.* To|',
            $tester->getDisplay());
    }
}
