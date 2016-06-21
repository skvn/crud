<?php namespace Skvn\Crud\Form;

use Illuminate\Container\Container;
use Skvn\Crud\Models\CrudStubModel;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Contracts\FormControl;



class Form {

    protected static $controls = [];

    /**
     * @var
     */
    public $config;
    /**
     * @var
     */
    public $crudObj;
    /**
     * @var
     */
    public $fields = [];
    public $tabs = [];

    public $customProperties;
    //public $visibleFields;

    /**
     * Form constructor.
     *
     * @param array $args
     */

    public function __construct($args = [])
    {
        $this->crudObj = isset($args['crudObj']) ? $args['crudObj'] : new CrudStubModel();
        $this->customProperties = isset($args['props']) ? $args['props'] : [];
        if (!empty($args['fields']) && is_array($args['fields']))
        {
            $this->setFields($args['fields']);
        }
        if (!empty($args['data']) && is_array($args['data']))
        {
            $this->import($args['data']);
        }
        if (!empty($args['tabs']))
        {
            $this->setTabs($args['tabs']);
        }
    }//

    static function registerControl($class)
    {
        $control = $class :: create();
        if (! $control instanceof FormControl)
        {
            throw new ConfigException("Invalid control class " . $class);
        }
        if (isset(self :: $controls[$control->controlType()]))
        {
            throw new ConfigException('Control already registered: ' . $class);
        }
        self :: $controls[$control->controlType()] = $control;
    }

    static function getAvailControls()
    {
        return self :: $controls;
    }

    static function create($args = [])
    {
        return new self($args);
    }

    static function createControl(CrudModel $model, $config)
    {
        $class = get_class(self :: $controls[$config['type']]);
        return $class :: create()->setConfig($config)->setModel($model);
//        $control = new $class($config);
//        $control->setModel($model);
//        return $control;
    }

    function addField($name, $config)
    {
        if (empty($config['name']))
        {
            $config['name'] = $name;
        }
        $this->config[$name] = $config;
        $this->fields[$name] = self :: createControl($this->crudObj, $config);
        if (isset($config['value']))
        {
            $this->fields[$name]->setValue($config['value']);
        }
        if (!$this->crudObj->getField($name))
        {
            $this->crudObj->addFormField($name, isset($config['title']) ? $config['title'] : '', isset($config['type']) ? $config['type'] : "text", $config);
        }
        return $this;
    }

    function setFields($fields)
    {
        
        $this->fields = [];
        foreach ($fields as $col => $colConfig)
        {
            $this->addField($col, $colConfig);
        }
        return $this;
    }

    function setCustomProperties($props)
    {
        $this->customProperties = $props;
        return $this;
    }

    function setTabs($tabs)
    {
        $this->tabs = $tabs;
        return $this;
    }

    function import($data)
    {
        foreach ($this->fields as $field)
        {
            $field->pullFromData($data);
        }
        return $this;
    }

    function load($data)
    {
        $this->import($data);
        //$this->crudObj->fillFromRequest($data);
        $this->sync();
    }

    function sync()
    {
        foreach ($this->fields as $field)
        {
            $field->pushToModel();
        }
    }


    public function __toString()
    {
        $app = Container :: getInstance();
        $res = $app['view']->make('crud::crud/form', ['crudObj' => $this->crudObj])->render();
        return (string) $res;
    }


//    static function getAttachFields()
//    {
//
//    }

    

    /**
     * Return One field object by it's field name
     *
     * @param $fieldName
     * @return array
     */
    public function getFieldByName($fieldName)
    {
        return $this->fields[$fieldName];
    }


    /**
     * Return class instance of the control by it's TYPE constant
     *
     * @param string $type
     * @return FormControl|null
     */
    public static function getControlByType(string $type)
    {
        return self::$controls[$type] ?? null;

    }

//    public function __isset($key)
//    {
//        return $this->crudObj->__isset($key);
//    }
//
//    public function __get($key)
//    {
//        return $this->crudObj->__get($key);
//    }
//
//    public function __set($key, $value)
//    {
//        $this->crudObj->__set($key, $value);
//    }




} 