<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class InterfaceMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:interface {name}';
    protected $description = 'Create a new GraphQL interface class';
    protected $type = 'Interface';

    protected function getStub()
    {
        return __DIR__.'/stubs/interface.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\GraphQL\Interfaces';
    }
}
