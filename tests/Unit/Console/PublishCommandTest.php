<?php

declare(strict_types = 1);
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
            ->onlyMethods([
                'copy',
                'isDirectory',
                'makeDirectory',
            ])
            ->getMock();
        $filesystemMock
            ->expects(self::exactly(1))
            ->method('copy')
            ->withConsecutive(
                [
                    self::callback(function (string $from): bool {
                        $this->assertMatchesRegularExpression('|/config/config.php|', $from, '1st call to copy, $from');

                        return true;
                    }),
                    self::callback(function (string $to): bool {
                        $this->assertMatchesRegularExpression(
                            '|laravel[/\\\\]config/graphql.php|',
                            $to,
                            '1st call to copy, $to'
                        );

                        return true;
                    }),
                ],
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make(PublishCommand::class);

        $tester = $this->runCommand($command);

        self::assertSame(0, $tester->getStatusCode());
        self::assertMatchesRegularExpression('|Copied File.*[/\\\\]config[/\\\\]config.php.* To|', $tester->getDisplay());
    }
}
