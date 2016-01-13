<?php


namespace Skvn\Crud\Wizard;


class CrudModelPrototype
{

    protected $config_data;
    protected $app;
    protected $namespace;
    protected $path;

    /**
     * CrudModelPrototype constructor.
     * @param $config_data
     */
    public function __construct($config_data)
    {

        $this->config_data = $config_data;

        $this->app = app();
        $this->namespace = $this->app['config']['crud_common.model_namespace'];
        $this->config_data['namespace'] = $this->namespace;
        $folderExpl = explode('\\',$this->namespace);
        $folder = $folderExpl[(count($folderExpl)-1)];
        $this->path = app_path($folder);
    }

    public  function record()
    {

        $this->app['view']->addNamespace('crud_wizard', __DIR__ . '/stubs');

        $this->recordConfig();
        $this->recordModels();


    }

    protected  function recordConfig()
    {

        $v = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $this->app['view']->make('crud_wizard::crud_config', ['model'=>$this->config_data])->render());
        $path = config_path('crud');
        @mkdir($path);
        file_put_contents($path.'/crud_'.$this->config_data['table'].'.php',$v);


    }

    protected  function  recordModels()
    {
        //record main model (ONLY ONCE)
        if (!file_exists($this->path.'/'.$this->config_data['name'].'.php'))
        {
            @mkdir($this->path);
            file_put_contents($this->path.'/'.$this->config_data['name'].'.php',
                $this->app['view']->make('crud_wizard::crud_model_class', ['model'=>$this->config_data])->render()
            );
        }

        //record base model
        @mkdir($this->path.'/Crud');
        file_put_contents($this->path.'/Crud/'.$this->config_data['name'].'Base.php',
            $this->app['view']->make('crud_wizard::crud_base_model_class', ['model'=>$this->config_data])->render()
        );


    }


}