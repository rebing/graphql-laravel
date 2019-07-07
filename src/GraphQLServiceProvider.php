<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use GraphQL\Validator\Rules\QueryDepth;
use Illuminate\Support\ServiceProvider;
use GraphQL\Validator\DocumentValidator;
use Rebing\GraphQL\Console\EnumMakeCommand;
use Rebing\GraphQL\Console\TypeMakeCommand;
use GraphQL\Validator\Rules\QueryComplexity;
use Rebing\GraphQL\Console\FieldMakeCommand;
use Rebing\GraphQL\Console\InputMakeCommand;
use Rebing\GraphQL\Console\QueryMakeCommand;
use Rebing\GraphQL\Console\UnionMakeCommand;
use Illuminate\Contracts\Container\Container;
use Rebing\GraphQL\Console\ScalarMakeCommand;
use Rebing\GraphQL\Console\MutationMakeCommand;
use Rebing\GraphQL\Console\InterfaceMakeCommand;
use GraphQL\Validator\Rules\DisableIntrospection;

class GraphQLServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootPublishes();

        $this->bootTypes();

        $this->bootSchemas();

        $this->bootRouter();
    }

    /**
     * Bootstrap router.
     *
     * @return void
     */
    protected function bootRouter(): void
    {
        if (config('graphql.routes')) {
            include __DIR__.'/routes.php';
        }
    }

    /**
     * Bootstrap publishes.
     *
     * @return void
     */
    protected function bootPublishes(): void
    {
        $configPath = __DIR__.'/../config';

        $this->mergeConfigFrom($configPath.'/config.php', 'graphql');

        $this->publishes([
            $configPath.'/config.php' => config_path('graphql.php'),
        ], 'config');

        $viewsPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewsPath, 'graphql');
    }

    /**
     * Bootstrap publishes.
     *
     * @return void
     */
    protected function bootTypes(): void
    {
        $configTypes = config('graphql.types');
        $this->app->make('graphql')->addTypes($configTypes);
    }

    /**
     * Add schemas from config.
     *
     * @return void
     */
    protected function bootSchemas(): void
    {
        $configSchemas = config('graphql.schemas');
        foreach ($configSchemas as $name => $schema) {
            $this->app->make('graphql')->addSchema($name, $schema);
        }
    }

    /**
     * Configure security from config.
     *
     * @return void
     */
    protected function applySecurityRules(): void
    {
        $maxQueryComplexity = config('graphql.security.query_max_complexity');
        if ($maxQueryComplexity !== null) {
            /** @var QueryComplexity $queryComplexity */
            $queryComplexity = DocumentValidator::getRule('QueryComplexity');
            $queryComplexity->setMaxQueryComplexity($maxQueryComplexity);
        }

        $maxQueryDepth = config('graphql.security.query_max_depth');
        if ($maxQueryDepth !== null) {
            /** @var QueryDepth $queryDepth */
            $queryDepth = DocumentValidator::getRule('QueryDepth');
            $queryDepth->setMaxQueryDepth($maxQueryDepth);
        }

        $disableIntrospection = config('graphql.security.disable_introspection');
        if ($disableIntrospection === true) {
            /** @var DisableIntrospection $disableIntrospection */
            $disableIntrospection = DocumentValidator::getRule('DisableIntrospection');
            $disableIntrospection->setEnabled(DisableIntrospection::ENABLED);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGraphQL();

        if ($this->app->runningInConsole()) {
            $this->registerConsole();
        }
    }

    public function registerGraphQL(): void
    {
        $this->app->singleton('graphql', function (Container $app): GraphQL {
            $graphql = new GraphQL($app);

            $this->applySecurityRules();

            return $graphql;
        });
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    public function registerConsole(): void
    {
        $this->commands(EnumMakeCommand::class);
        $this->commands(FieldMakeCommand::class);
        $this->commands(InputMakeCommand::class);
        $this->commands(InterfaceMakeCommand::class);
        $this->commands(InterfaceMakeCommand::class);
        $this->commands(MutationMakeCommand::class);
        $this->commands(QueryMakeCommand::class);
        $this->commands(ScalarMakeCommand::class);
        $this->commands(TypeMakeCommand::class);
        $this->commands(UnionMakeCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['graphql'];
    }
}
