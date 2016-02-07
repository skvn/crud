<?php namespace Skvn\Crud\Wizard;

use Skvn\Crud\CrudConfig;
use Skvn\Crud\Form\Form;


/**
 * Class Wizard
 * @package Skvn\Crud
 * @author Vitaly Nikolenko <vit@webstandart.ru>
 */
class Wizard
{


    /**
     * @var null bool|null
     */
    private $is_models_defined = null;
    /**
     * @var
     */
    private $app;
    /**
     * @var
     */
    private $model_configs;
    /**
     * @var
     */
    private $available_models;
    /**
     * @var
     */
    private $table_columns;

    /**
     * @var
     */
    private $table_int_columns;

    /**
     * @var
     */

    private $crud_configs;
    /**
     * @var
     */
    private $table_column_types;

    /**
     * @var string Path to crud config storage directory
     */
    public $config_dir_path = '';

    /**
     * @var string Path to model directory
     */
    public $model_dir_path = '';


    /**
     * Wizard constructor.
     */
    function __construct()
    {
        $this->app = app();
        $this->config_dir_path =  config_path('crud');

        $folderExpl = explode('\\',$this->app['config']['crud_common.model_namespace']);
        $folder = $folderExpl[(count($folderExpl)-1)];
        $this->model_dir_path = app_path($folder);
    }

    /**
     * @return bool Check if config directory is writable
     */

    public  function checkConfigDir()
    {

        return (is_dir($this->config_dir_path) && is_writable($this->config_dir_path));

    }//

    /**
     * @return bool Check if models directory is writable
     */

    public  function checkModelsDir()
    {

        return (is_dir($this->model_dir_path) && is_writable($this->model_dir_path));

    }//

    /**
     * Return db tables
     * @return array
     */
    function getTables()
    {

        $tables = $this->app['db']->select('SELECT  table_name FROM   information_schema.tables WHERE   table_type = \'BASE TABLE\' AND   table_schema=?', [env('DB_DATABASE')]);
        $arr = [];

        foreach ($tables as $table)
        {

            if (strpos($table->table_name,'crud_') !==0 && strpos($table->table_name,'crud_file') === false)
            {
                $arr[] = $table->table_name;
            }

        }

        $return = [];
        foreach ($arr as $k=> $table_name)
        {
            if ($this->getModelConfig($table_name))
            {
                $return[] = $table_name;
                unset($arr[$k]);
            }
        }

        $return += $arr;
        return $return;

    }

    /**
     * Return columns for a specific table
     * @param $table
     * @return mixed
     */
    function getTableColumns($table)
    {
        if (!isset($this->table_columns[$table]))
        {
            $this->table_columns[$table] = $this->app['db']->connection()->getSchemaBuilder()->getColumnListing($table);
        }
        return $this->table_columns[$table];
    }

    /**
     * Return INT columns for a specific table
     * @param $table
     * @return mixed
     */
    function getIntTableColumns($table)
    {
        if (!$this->table_int_columns) {

            $this->table_int_columns = [];
            $col_types = $this->getTableColumnTypes($table);

            foreach ($col_types as $col_name => $col_type) {

                $col_type = strtolower($col_type);

                if (strpos($col_type, 'int') !== false) {
                    $this->table_int_columns[] = $col_name;
                }
            }
        }

        return $this->table_int_columns;
    }

    /**
     * Return table column types in  column=>data_type format
     * @param $table
     * @return mixed
     */
    function getTableColumnTypes($table)
    {
        if (!isset($this->table_column_types[$table]))
        {

            $cols =  $this->app['db']->select('SELECT  COLUMN_NAME, DATA_TYPE FROM   information_schema.COLUMNS WHERE   TABLE_SCHEMA = ? AND TABLE_NAME=?', [env('DB_DATABASE'),$table]);
            foreach ($cols as $col)
            {
                $this->table_column_types[$table][$col->COLUMN_NAME] = $col->DATA_TYPE;
            }

        }
        return $this->table_column_types[$table];
    }

    /**
     * Get crud models already defined
     * @return array
     */
    function getAvailableModels()
    {
        if (!$this->available_models)
        {

            $configs = $this->getCrudConfigs();
            if ($configs)
            {
                $this->available_models = array_keys($configs);
            }
        }

        return $this->available_models;
    }

    /**
     * Get crud-model config
     * @param $table_name Table name
     * @param $force If use cached values or force recreate
     * @return mixed
     */
    function getModelConfig($table_name, $force=false)
    {

        if (!isset($this->model_configs[$table_name]) || $force)
        {
            if (file_exists(config_path('crud/crud_'.$table_name.'.php')))
            {
                $this->model_configs[$table_name] = $this->app['config']->get('crud.crud_'.$table_name);
            } else {
                $this->model_configs[$table_name] = false;
            }
        }

        return $this->model_configs[$table_name];

    }


    /**
     * Detect if any crud-models are already configured
     * @return bool|null
     */
    function modelsDefined()
    {
        if ($this->is_models_defined === null)
        {
            $configs = $this->getCrudConfigs();
            if (count($configs))
            {
                $this->is_models_defined = true;
            } else {
                $this->is_models_defined = false;
            }
        }

        return $this->is_models_defined;
    }

    /**
     * Get all crud configs
     * @return array
     */
    private function getCrudConfigs()
    {
        if (!$this->crud_configs)
        {
            $this->crud_configs = [];
            $files =  \File::files(config_path('crud'));
            foreach ($files as $file)
            {
                $cfg = include ($file);
                $table = str_replace(['crud_', '.php'],'', basename($file));
                $cfg['table'] = $table;
                $this->crud_configs[$cfg['name']] = $cfg;
            }
        }

        return $this->crud_configs;
    }

    /**
     * Get an array of available form field types
     * @return array
     */
    function getAvailableFieldTypes()
    {
        return Form::getAvailableFieldTypes();
    }

    /**
     * Get an array of available filter field types
     * @return array
     */
    function getAvailableFilterTypes()
    {
        return Form::getAvailableFilterTypes();
    }


    /**
     * Get an array of available form field types for  relations
     * @return array
     */
    function getAvailableRelationFieldTypes()
    {
        return CrudConfig::getAvailableRelationFieldTypes();
    }

    /**
     * Get an array of all columns for all crud-models
     * @return array
     */
    function getAllModelColumns()
    {
        $ret = [];
        $configs = $this->getCrudConfigs();
        foreach ($configs as $model=>$cfg)
        {
            $ret[snake_case($model)] = $this->getTableColumns($cfg['table']);
        }
        return $ret;

    }//

    /**
     * @return array List of availabe date formats in php and js forms
     */
    function getAvailableDateFormats()
    {

        return [
            ['js'=>'dd.mm.yyyy', 'php' => 'd.m.Y' ],
            ['js'=>'dd/mm/yyyy', 'php' => 'd/m/Y' ],
            ['js'=>'dd-mm-yyyy', 'php' => 'd-m-Y' ],
            ['js'=>'mm/dd/yyyy', 'php' => 'm/d/Y' ],
            ['js'=>'mm/dd/yy', 'php' => 'm/d/y' ],
            ['js'=>'yyyy-mm-dd', 'php' => 'Y-m-d' ],


        ];

    }


}
