<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class ScalarMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:scalar {name}';
    protected $description = 'Create a new GraphQL scalar class';
    protected $type = 'Scalar';

    protected function getStub()
    {
        return __DIR__.'/stubs/scalar.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\GraphQL\Scalars';
    }
}
