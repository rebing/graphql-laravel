<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class InputMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:input {name}';
    protected $description = 'Create a new GraphQL input class';
    protected $type = 'Input';

    protected function getStub()
    {
        return __DIR__.'/stubs/input.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\GraphQL\Inputs';
    }
}
