<?php namespace Rebing\GraphQL;

use Illuminate\Support\ServiceProvider;

class GraphQLServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootPublishes();

        $this->bootTypes();

        if(config('graphql.routes'))
        {
            include __DIR__.'/routes.php';
        }
    }

    /**
     * Bootstrap publishes
     *
     * @return void
     */
    protected function bootPublishes()
    {
        $configPath = __DIR__.'/../../config';

        $this->mergeConfigFrom($configPath.'/config.php', 'graphql');

        $this->publishes([
            $configPath.'/config.php' => config_path('graphql.php'),
        ], 'config');
    }

    /**
     * Bootstrap publishes
     *
     * @return void
     */
    protected function bootTypes()
    {
        $configTypes = config('graphql.types');
        foreach($configTypes as $name => $type)
        {
            if(is_numeric($name))
            {
                $this->app['graphql']->addType($type);
            }
            else
            {
                $this->app['graphql']->addType($type, $name);
            }
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
    }

    public function registerGraphQL()
    {
        $this->app->singleton('graphql', function($app)
        {
            return new GraphQL($app);
        });
    }
}
