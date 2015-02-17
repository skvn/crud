<?php


namespace LaravelCrud\Form;


class Field
{

    public $config;
    protected $value;
    protected $form;
    public $name;

    function __construct(\LaravelCrud\Form\Form $form,$config )
    {
        $this->config = $config;
        $this->form = $form;
        $this->name = $config['name'];

        if (!$this->validateConfig())
        {
            throw new \Exception('Column '.$this->name.' is not well described');
        }
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

    function validateConfig()
    {
        return true;
    }



} 