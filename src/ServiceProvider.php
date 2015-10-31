<?php namespace LaravelCrud;

use Illuminate\Support\ServiceProvider as LServiceProvider;


class ServiceProvider extends LServiceProvider {


    public function boot()
    {
        $paths = [];
        foreach (\Config :: get("view.paths") as $path)
        {
            $paths[] = $path . "/crud";
        }
        $this->app['view']->getFinder()->prependNamespace("crud", $paths);
        $this->app['config']['aliases']['Crud'] = \LaravelCrud\Facades\Crud :: class;
    }

    public function register()
    {
        $this->publishes([__DIR__ . '/../config/' => config_path() . "/"], 'config');
        $this->publishes([__DIR__ . '/../public/' => public_path() . "/vendor/crud/"], 'assets');
        $this->publishes([__DIR__ . '/../database/' => base_path("database")], 'database');
        $this->loadViewsFrom(__DIR__.'/views', 'crud');

        $this->app->singleton('CmsHelper',function()
        {
            return new \LaravelCrud\Helper\CmsHelper(\Auth::user());
        });

        $this->app->singleton('CrudHelper',function()
        {
            return new \LaravelCrud\Helper\CrudHelper($this->app);
        });
        //$this->app->bind('Crud', \LaravelCrud\Facades\Crud :: class);

        $this->registerMakeTreeGenerator();

    }

    /**
     * Register the crud:make_tree generator.
     */
    private function registerMakeTreeGenerator()
    {
        $this->app->singleton('command.skvn.crud.tree', function ($app) {
            return $app['LaravelCrud\Commands\CrudTreeCommand'];
        });

        $this->commands('command.skvn.crud.tree');
    }



    public function provides()
    {
        return array();
    }
}