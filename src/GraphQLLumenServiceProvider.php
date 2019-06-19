<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Rebing\GraphQL\Console\PublishCommand;

class GraphQLLumenServiceProvider extends GraphQLServiceProvider
{
    protected function bootPublishes(): void
    {
        $configPath = __DIR__.'/../config';

        $this->mergeConfigFrom($configPath.'/config.php', 'graphql');

        $viewsPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewsPath, 'graphql');
    }

    public function register()
    {
        if (class_exists('Laravel\Lumen\Routing\Controller')) {
            class_alias('Laravel\Lumen\Routing\Controller', 'Illuminate\Routing\Controller');
        }

        parent::register();
    }

    public function registerConsole(): void
    {
        parent::registerConsole();

        $this->commands(PublishCommand::class);
    }
}
