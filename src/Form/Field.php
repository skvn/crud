<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudModel;


abstract class Field
{

    const SELECT = 'select';
    const TEXT = 'text';
    const FILE = 'file';
    const IMAGE = 'image';
    const CHECKBOX = 'checkbox';
    const MULTI_FILE = 'multi_file';
    const TEXTAREA = 'textarea';
    const DATE = 'date';
    const DATE_TIME = 'date_time';
    const RANGE = 'range';
    const DATE_RANGE = 'date_range';
    const NUMBER = 'number';
    const DECIMAL = 'decimal';
    const TAGS = 'tags';
    const TREE = 'tree';

    const TYPE = "abstract";


    public $config;
    protected $value = null;
    public $model;
    public $name;
    protected $field;
    protected $uniqid;
    protected $filtrable = false;


    static function create()
    {
        return new static();
    }

    function setModel(CrudModel $model)
    {
        $this->model = $model;
        $this->getValue();
        return $this;
    }

    function setConfig($config)
    {
        if (empty($config['field']))
        {
            $config['field'] = $config['name'];
        }
        $this->config = $config;
        $this->name = $config['name'];
        $this->field = $config['field'];

        if (!$this->validateConfig())
        {
            throw new ConfigException('Column '.$this->name.' is not well described');
        }

        return $this;
    }

    function controlWidgetUrl()
    {
        return false;
    }

    function getFilterCondition()
    {
        if (!$this->filtrable)
        {
            return false;
        }
        if (!empty($this->value)) {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
            return ['cond' => [$col, '=',  $this->value ]];
        }
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
        $this->value = isset($data[$this->field]) ? $data[$this->field] : null;
    }

    function getName()
    {
        return $this->name;
    }

    function getField()
    {
        return $this->field;
    }

    function getConfig(){
        return $this->config;
    }

    function getFilterColumnName()
    {
        return (!empty($this->config['filter_column'])?$this->config['filter_column']:$this->field);
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

    function syncValue()
    {
        $this->model->setAttribute($this->name, $this->prepareValueForDb($this->value));
        //$this->model->{$this->name} = $this->prepareValueForDb($this->value);
    }



} 