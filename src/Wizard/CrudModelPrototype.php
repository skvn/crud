<?php


namespace Skvn\Crud\Wizard;


use Skvn\Crud\CrudWizardException;

class CrudModelPrototype
{

    protected $config_data, $app, $namespace, $path, $config_path;

    /**
     * CrudModelPrototype constructor.
     * @param $config_data
     */
    public function __construct($config_data)
    {

        if (empty($config_data['table']))
        {
            throw new CrudWizardException('Table  for model prototype is not defined');
        }

        $this->config_data = $config_data;
        $this->app = app();
        $this->namespace = $this->app['config']['crud_common.model_namespace'];
        $this->config_data['namespace'] = $this->namespace;
        $folderExpl = explode('\\',$this->namespace);
        $folder = $folderExpl[(count($folderExpl)-1)];
        $this->path = app_path($folder);
        $this->config_path = config_path('crud').'/crud_'.$this->config_data['table'].'.php';
        @mkdir(dirname($this->config_path));
        if (file_exists($this->config_path))
        {
            $old_config = include $this->config_path;
            $this->config_data = array_merge($old_config,$this->config_data);
        }

        $this->prepareConfigData();

    }//

    private function prepareConfigData()
    {

        if (!empty($this->config_data['fields']))
        {
            $form_fields = [];
            foreach ($this->config_data['fields'] as $key=>$f)
            {
                if (!empty($f['type']) && (!isset($f['editable']) || $f['editable'] ))
                {
                    $form_fields[] = $key;
                }

            }

            $this->config_data['form_fields'] = $form_fields;
        }

    }//

    public  function record()
    {

        $this->app['view']->addNamespace('crud_wizard', __DIR__ . '/../stubs');

        $this->recordConfig();
        $this->recordModels();


    }

    protected  function recordConfig()
    {

        //FIXME need some good formatting
        //$v = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $this->app['view']->make('crud_wizard::crud_config', ['model'=>$this->config_data])->render());
        $val = $this->app['view']->make('crud_wizard::crud_config', ['model'=>$this->config_data])->render();
        file_put_contents($this->config_path,$val);

    }//

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


    }//


}