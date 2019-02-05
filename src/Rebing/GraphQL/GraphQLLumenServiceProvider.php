<?php

namespace Rebing\GraphQL;

use Rebing\GraphQL\Console\PublishCommand;

class GraphQLLumenServiceProvider extends GraphQLServiceProvider
{
    protected function bootPublishes()
    {
        $configPath = __DIR__.'/../../config';

        $this->mergeConfigFrom($configPath.'/config.php', 'graphql');

        $viewsPath = __DIR__.'/../../resources/views';
        $this->loadViewsFrom($viewsPath, 'graphql');
    }

    public function register()
    {
        class_alias('Laravel\Lumen\Routing\Controller', 'Illuminate\Routing\Controller');

        parent::register();
    }

    public function registerConsole()
    {
        parent::registerConsole();

        $this->commands(PublishCommand::class);
    }
}