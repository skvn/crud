<?php namespace Skvn\Crud\Form;

use Illuminate\Container\Container;
use Skvn\Crud\Models\CrudStubModel;
use Skvn\Crud\Exceptions\ConfigException;



class Form {

    static $controls = [];

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
        if (!empty($args['config']) && is_array($args['config']))
        {
            $this->setFields($args['config']);
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
        if (!defined($class . "::TYPE"))
        {
            throw new ConfigException("Invalid control class " . $class);
        }
        $conf =  [
            'type' => $class :: TYPE,
            'class' => $class,
            'widget_url' => $class :: controlWidgetUrl(),
            'caption' => $class :: controlCaption(),
            'filtrable' => $class :: controlFiltrable()
        ];
        if (isset(self :: $controls[$class :: TYPE]))
        {
            throw new ConfigException('Control already registered: ' . $class);
        }
        self :: $controls[$conf['type']] = $conf;
    }

    static function create($args = [])
    {
        return new self($args);
    }

    function addField($name, $config)
    {
        if (empty($config['name']))
        {
            $config['name'] = $name;
        }
        $this->config[$name] = $config;
        $this->fields[$name] = Field::create($this->crudObj, $config);
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
            $field->importValue($data);
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
            $field->syncValue();
        }
    }


    public function __toString()
    {
        $app = Container :: getInstance();
        $res = $app['view']->make('crud::crud/form', ['crudObj'=>$this->crudObj])->render();
        return (string) $res;
    }


    static function getAttachFields()
    {

    }

    

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
     * Return class name of the control by it's TYPE constant
     *
     * @param string $type
     * @return string|null
     */
    public static function resolveControlClassByType(string $type)
    {
        return self::$controls[$type]['class'] ?? null;

    }

    public function __isset($key)
    {
        return $this->crudObj->__isset($key);
    }

    public function __get($key)
    {
        return $this->crudObj->__get($key);
    }

    public function __set($key, $value)
    {
        $this->crudObj->__set($key, $value);
    }




} 