<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class EnumMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:enum {name}';
    protected $description = 'Create a new GraphQL enum class';
    protected $type = 'Enum';

    protected function getStub()
    {
        return __DIR__.'/stubs/enum.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\GraphQL\Enums';
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->replaceGraphqlName($stub);
    }

    protected function replaceGraphqlName(string $stub): string
    {
        $graphqlName = $this->getNameInput();
        $graphqlName = preg_replace('/Type$/', '', $graphqlName);

        return str_replace(
            'DummyGraphqlName',
            $graphqlName,
            $stub
        );
    }
}
