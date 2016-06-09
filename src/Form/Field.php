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


    public $config;
    protected $value = null;
    public $model;
    public $name;
    protected $field;
    protected $uniqid;
    protected $filtrable = false;

    function __construct(CrudModel $model, $config)
    {
        if (empty($config['field']))
        {
            $config['field'] = $config['name'];
        }
        $this->config = $config;
        $this->model = $model;
        $this->name = $config['name'];
        $this->field = $config['field'];
        $this->getValue();

        if (!$this->validateConfig())
        {
            throw new ConfigException('Column '.$this->name.' is not well described');
        }
    }

    public static  function create(CrudModel $model, $config)
    {
        //$type = 'Skvn\Crud\Form\\'.studly_case($config['type']);
        //$type = studly_case($config['type']);
        //var_dump(Form :: $controls);
        $class = Form :: $controls[$config['type']]['class'];
        return new $class($model, $config);
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


    function getTemplate()
    {
        return Form :: $controls[$this->config['type']]['template'];
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