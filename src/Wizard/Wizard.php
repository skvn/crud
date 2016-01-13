<?php

namespace Skvn\Crud\Wizard;

use Skvn\Crud\CrudConfig;


class Wizard
{


    private $is_models_defined = null;
    private $app, $model_configs, $available_models, $table_columns, $crud_configs,$table_column_types;

    function __construct()
    {
        $this->app = app();
    }

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

        return $arr;

    }

    function getTableColumns($table)
    {
        if (!isset($this->table_columns[$table]))
        {
            $this->table_columns[$table] = $this->app['db']->connection()->getSchemaBuilder()->getColumnListing($table);
        }
        return $this->table_columns[$table];
    }

    function getTableColumnTypes($table)
    {
        if (!isset($this->table_column_types[$table]))
        {

            $cols = $tables = $this->app['db']->select('SELECT  COLUMN_NAME, DATA_TYPE FROM   information_schema.COLUMNS WHERE   TABLE_SCHEMA = ? AND TABLE_NAME=?', [env('DB_DATABASE'),$table]);
            foreach ($cols as $col)
            {
                $this->table_columns[$table][$col->COLUMN_NAME] = $col->DATA_TYPE;
            }

        }
        return $this->table_columns[$table];
    }

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

    function getModelConfig($table_name)
    {

        if (!isset($this->model_configs[$table_name]))
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

    function getAvailableFieldTypes()
    {
        return CrudConfig::getAvailableFieldTypes();
    }

    function getAvailableRelationFieldTypes()
    {
        return CrudConfig::getAvailableRelationFieldTypes();
    }

    function getAllModelColumns()
    {
        $ret = [];
        $configs = $this->getCrudConfigs();
        foreach ($configs as $model=>$cfg)
        {
            $ret[$model] = $this->getTableColumns($cfg['table']);
        }
        return $ret;

    }

    static public  function  saveModel(\Illuminate\Foundation\Application $app)
    {

//        $app['view']->addNamespace('crud_wizard', __DIR__ . '/../stubs');
//        $v = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $app['view']->make('crud_wizard::crud_config', ['model'=>$app['request']->all()])->render());
//        print_r($app['request']->all());
//        print_r($v);
//        exit;
    }

}