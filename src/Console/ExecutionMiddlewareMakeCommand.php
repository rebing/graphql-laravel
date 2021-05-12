<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class ExecutionMiddlewareMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:executionMiddleware {name}';
    protected $description = 'Create a new GraphQL execution middleware class';
    protected $type = 'ExecutionMiddleware';

    protected function getStub()
    {
        return __DIR__ . '/stubs/executionMiddleware.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\GraphQL\Middleware\Execution';
    }
}
