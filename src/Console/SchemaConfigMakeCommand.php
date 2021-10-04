<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class SchemaConfigMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:schemaConfig {name}';
    protected $description = 'Create a new GraphQL schema configuration class';
    protected $type = 'Schema';

    protected function getStub()
    {
        return __DIR__ . '/stubs/schemaConfig.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\GraphQL\Schemas';
    }
}
