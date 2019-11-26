<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\QueryMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class QueryMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    /**
     * @dataProvider dataForMakeCommand
     * @param  string  $inputName
     * @param  string  $expectedFilename
     * @param  string  $expectedClassDefinition
     * @param  string  $expectedGraphqlName
     */
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition,
        string $expectedGraphqlName
    ): void {
        $this->assertMakeCommand(
            'Query',
            QueryMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Queries',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Queries/Example.php',
                'expectedClassDefinition' => 'Example extends Query',
                'expectedGraphqlName' => "'name' => 'example',",
            ],
            'ExampleQuery' => [
                'inputName' => 'ExampleQuery',
                'expectedFilename' => 'GraphQL/Queries/ExampleQuery.php',
                'expectedClassDefinition' => 'ExampleQuery extends Query',
                'expectedGraphqlName' => "'name' => 'example',",
            ],
        ];
    }
}
