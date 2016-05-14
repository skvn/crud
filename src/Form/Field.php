<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Exceptions\ConfigException;


abstract class Field
{

    public $config;
    protected $value = null;
    protected $form;
    public $name;
    protected $uniqid;

    function __construct(Form $form,$config )
    {
        $this->config = $config;
        $this->form = $form;
        $this->name = $config['name'];

        if (!$this->validateConfig())
        {
            throw new ConfigException('Column '.$this->name.' is not well described');
        }


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

    function setValue($val){


        $this->value =  $val;
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



} 