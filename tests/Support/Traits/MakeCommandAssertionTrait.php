<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Traits;

use Illuminate\Filesystem\Filesystem;
use Rebing\GraphQL\Tests\TestCase;

/**
 * @mixin TestCase
 */
trait MakeCommandAssertionTrait
{
    protected function assertMakeCommand(
        string $graphqlKind,
        string $makeCommandClassName,
        string $inputName,
        string $expectedFilename,
        string $expectedNamespace,
        string $expectedClassDefinition,
        string $expectedGraphqlName = null
    ): void {
        $filesystemMock = $this
            ->getMockBuilder(Filesystem::class)
            ->onlyMethods([
                'isDirectory',
                'makeDirectory',
                'put',
            ])
            ->getMock();
        $filesystemMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->callback(function (string $path) use ($expectedFilename): bool {
                    $this->assertMatchesRegularExpression("|laravel[/\\\\]app/$expectedFilename|", $path);

                    return true;
                }),
                $this->callback(function (string $contents) use ($expectedClassDefinition, $expectedGraphqlName, $expectedNamespace): bool {
                    $this->assertMatchesRegularExpression("/namespace $expectedNamespace;/", $contents);
                    $this->assertMatchesRegularExpression("/class $expectedClassDefinition/", $contents);

                    if ($expectedGraphqlName) {
                        $this->assertMatchesRegularExpression("/$expectedGraphqlName/", $contents);
                    }

                    return true;
                })
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make($makeCommandClassName);

        $tester = $this->runCommand($command, [
            'name' => $inputName,
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression("/$graphqlKind created successfully/", $tester->getDisplay());
    }
}
