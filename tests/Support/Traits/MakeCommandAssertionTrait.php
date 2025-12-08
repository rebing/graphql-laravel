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
        ?string $expectedGraphqlName = null,
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
            ->expects(self::once())
            ->method('put')
            ->with(
                self::callback(function (string $path) use ($expectedFilename): bool {
                    $this->assertMatchesRegularExpression("|laravel[/\\\\]app/$expectedFilename|", $path);

                    return true;
                }),
                self::callback(function (string $contents) use ($expectedClassDefinition, $expectedGraphqlName, $expectedNamespace): bool {
                    $this->assertMatchesRegularExpression("/namespace $expectedNamespace;/", $contents);
                    $this->assertMatchesRegularExpression("/class $expectedClassDefinition/", $contents);

                    if ($expectedGraphqlName) {
                        $this->assertMatchesRegularExpression("/$expectedGraphqlName/", $contents);
                    }

                    return true;
                }),
            );
        $this->instance(Filesystem::class, $filesystemMock);

        $command = $this->app->make($makeCommandClassName);

        $tester = $this->runCommand($command, [
            'name' => $inputName,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertMatchesRegularExpression("/$graphqlKind.*created successfully/", $tester->getDisplay());
    }
}
