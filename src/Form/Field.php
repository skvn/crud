<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudModel;


class Field
{

    public $config;
    protected $value = null;
    public $model;
    public $name;
    protected $uniqid;

    function __construct(CrudModel $model, $config)
    {
        $this->config = $config;
        $this->model = $model;
        $this->name = $config['name'];

        if (!$this->validateConfig())
        {
            throw new ConfigException('Column '.$this->name.' is not well described');
        }
    }

    public static  function create(CrudModel $model, $config)
    {
        $type = 'Skvn\Crud\Form\\'.studly_case($config['type']);
        //$type = studly_case($config['type']);
        return new $type($model, $config);
    }


    function getFilterCondition()
    {
        return false;
    }

    function  getUniqueId()
    {
        if (!$this->uniqid)
        {
            $this->uniqid = uniqid($this->name);
        }
        return $this->uniqid;
    }
    function getValue()
    {
        return $this->value;
    }

    function setValue($val)
    {
        $this->value =  $val;
    }

    function importValue($data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    function getName()
    {
        return $this->name;
    }

    function getConfig(){
        return $this->config;
    }

    function getFilterColumnName()
    {
        return (!empty($this->config['filter_column'])?$this->config['filter_column']:$this->name);
    }

    function  validate()
    {
        return true;
    }

    function  getValueForDb()
    {
        return $this->getValue();
    }

    function  getValueForList()
    {
        return $this->getValue();
    }

    function validateConfig()
    {
        return true;
    }

    function prepareValueForDb($value)
    {
        return $value;
    }



} 