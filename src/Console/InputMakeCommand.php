<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('make:graphql:input')]
class InputMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:input {name} {--oneof : Generate a OneOf input type}';
    protected $description = 'Create a new GraphQL input class';
    protected $type = 'Input';

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/input.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\GraphQL\Inputs';
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $stub = $this->replaceGraphqlName($stub);

        if ($this->option('oneof')) {
            $stub = $this->enableOneOf($stub);
        }

        return $stub;
    }

    protected function replaceGraphqlName(string $stub): string
    {
        $graphqlName = $this->getNameInput();
        $graphqlName = str_replace('InputObject', 'Input', $graphqlName);
        $graphqlName = \Safe\preg_replace('/Type$/', '', $graphqlName);

        return str_replace(
            'DummyGraphqlName',
            $graphqlName,
            $stub,
        );
    }

    protected function enableOneOf(string $stub): string
    {
        return str_replace(
            "'isOneOf' => false",
            "'isOneOf' => true",
            $stub
        );
    }
}
