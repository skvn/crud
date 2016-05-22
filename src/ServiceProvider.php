<?php namespace Skvn\Crud;

use Illuminate\Support\ServiceProvider as LServiceProvider;


class ServiceProvider extends LServiceProvider {


    public function boot()
    {

        $this->app['db']->setFetchMode(\PDO :: FETCH_ASSOC);
        //Messages
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'crud');

        //Assets
        if (!$this->isLumen())
        {
            $this->publishes([__DIR__ . '/../config/' => config_path() . "/"], 'config');
            $this->publishes([__DIR__ . '/../public/' => public_path() . "/vendor/crud/"], 'assets');
            $this->publishes([__DIR__ . '/../database/' => base_path("database")], 'database');
        }

        //Views
        $paths = [];

        foreach ($this->app['config']->get("view.paths") as $path)
        {
            $paths[] = $path . "/crud";
        }

        $this->app['view']->getFinder()->prependNamespace("crud", $paths);

        $this->loadViewsFrom(__DIR__.'/views', 'crud');

        // Routing
        include __DIR__ . DIRECTORY_SEPARATOR . 'routes.php';

    }

    public function register()
    {
        $this->registerCommands();
        $this->registerHelpers();

        // Register dependency packages
        $this->app->register('Intervention\Image\ImageServiceProvider');

        // Register dependancy aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Crud', Facades\Crud :: class);
        $loader->alias('Cms', Facades\Cms :: class);
        $loader->alias('Image', \Intervention\Image\Facades\Image::class);



//        $this->app->singleton('CmsHelper',function()
//        {
//            return new \Skvn\Crud\Helper\CmsHelper(\Auth::user());
//        });
//
//        $this->app->singleton('CrudHelper',function()
//        {
//            return new \Skvn\Crud\Helper\CrudHelper($this->app);
//        });
        //$this->app->bind('Crud', \Skvn\Crud\Facades\Crud :: class);

        //$this->registerMakeTreeGenerator();

    }

    /**
     * Register the crud:make_tree generator.
     */
//    private function registerMakeTreeGenerator()
//    {
//        $this->app->singleton('command.skvn.crud.tree', function ($app) {
//            return $app['Skvn\Crud\Commands\CrudTreeCommand'];
//        });
//
//        $this->commands('command.skvn.crud.tree');
//    }
    protected function registerCommands()
    {
       
    }

    protected function registerHelpers()
    {
        $this->app->bindIf('skvn.cms', function($app){
            return new Helper\CmsHelper($app['auth']->user());
        }, true);
        $this->app->bindIf('skvn.crud', function($app){
            return new Helper\CrudHelper($app);
        }, true);
    }


    public function provides()
    {
        return array([
            'skvn.cms',
            'skvn.crud'
        ]);
    }

    protected function isLumen()
    {
        return strpos($this->app->version(), 'Lumen') !== false;
    }

}