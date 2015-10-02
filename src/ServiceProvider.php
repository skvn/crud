<?php
namespace LaravelCrud;

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

    }


    public function provides()
    {
        return array();
    }
}