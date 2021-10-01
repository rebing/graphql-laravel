<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class SchemaMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:schema {name}';
    protected $description = 'Create a new GraphQL schema class';
    protected $type = 'Schema';

    protected function getStub()
    {
        return __DIR__ . '/stubs/schema.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\GraphQL\Schemas';
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->replaceGraphqlName($stub);
    }

    protected function replaceGraphqlName(string $stub): string
    {
        $graphqlName = lcfirst($this->getNameInput());
        $graphqlName = preg_replace('/Schema$/', '', $graphqlName);

        return str_replace(
            'DummyGraphqlName',
            $graphqlName,
            $stub
        );
    }
}
