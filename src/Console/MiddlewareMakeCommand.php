<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\GeneratorCommand;

class MiddlewareMakeCommand extends GeneratorCommand
{
    protected $signature = 'make:graphql:middleware {name}';
    protected $description = 'Create a new GraphQL middleware class';
    protected $type = 'Middleware';

    protected function getStub()
    {
        return __DIR__ . '/stubs/middleware.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\GraphQL\Middleware';
    }
}
