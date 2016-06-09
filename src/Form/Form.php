<?php namespace Skvn\Crud\Form;

use Illuminate\Container\Container;
use Skvn\Crud\Models\CrudStubModel;
use Skvn\Crud\Exceptions\ConfigException;


/**
 * Class Form
 * @package Skvn\Crud
 * @author  Vitaly Nikolenko <vit@webstandart.ru>
 */
class Form {

    static $controls = [];

//    /**
//     *
//     */
//    const FIELD_SELECT = 'select';
//    /**
//     *
//     */
//    const FIELD_TEXT = 'text';
//    /**
//     *
//     */
//    const FIELD_FILE = 'file';
//    const FIELD_IMAGE = 'image';
//    /**
//     *
//     */
//    const FIELD_CHECKBOX = 'checkbox';
//    /**
//     *
//     */
//    const FIELD_MULTI_FILE = 'multi_file';
//    /**
//     *
//     */
//    const FIELD_TEXTAREA = 'textarea';
//    /**
//     *
//     */
//    const FIELD_DATE = 'date';
//
//    /**
//     *
//     */
//    const FIELD_DATE_TIME = 'date_time';
//
//    /**
//     *
//     */
//    const FIELD_RANGE = 'range';
//    /**
//     *
//     */
//    const FIELD_DATE_RANGE = 'date_range';
//    /**
//     *
//     */
//    const FIELD_NUMBER = 'number';
//
//    /**
//     *
//     */
//    const FIELD_DECIMAL = 'decimal';
//
//    /**
//     *
//     */
//    const FIELD_TAGS = 'tags';
//
//    /**
//     *
//     */
//    const FIELD_TREE = 'tree';




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
     * @param $crudObj
     * @param $config
     * @param null $data
     * @param array $customProperties
     */
    //public function __construct($crudObj, $config, $data=null, $customProperties = [])
    public function __construct($args = [])
    {
        $this->crudObj = isset($args['crudObj']) ? $args['crudObj'] : new CrudStubModel();
//        $this->config = $args['config'];
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
        if (!property_exists($class, 'controlInfo'))
        {
            throw new ConfigException('Invalid control class: ' . $class);
        }
        $conf = $class :: $controlInfo ?? [];
        if (empty($conf))
        {
            throw new ConfigException('Invalid control class: ' . $class);
        }
        if (empty($conf['type']))
        {
            throw new ConfigException('No type defined for control class: ' . $class);
        }
        if (isset(self :: $controls[$conf['type']]))
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
     * Get array of available edit types
     * @return array
     */
    static function getAvailableFieldTypes()
    {
        $types = [];
        foreach (self :: $controls as $control)
        {
            $types[$control['type']] = $control['caption'] ?? "---";
        }
        return $types;
//        return [
//            self::FIELD_TEXT => 'Text input',
//            self::FIELD_NUMBER => 'Number input',
//            self::FIELD_TEXTAREA => 'Textarea',
//            self::FIELD_DATE => 'Date',
//            self::FIELD_DATE_TIME => 'Date + Time',
//            self::FIELD_DATE_RANGE => 'Date range',
//            self::FIELD_SELECT => 'Select',
//            self::FIELD_CHECKBOX => 'Checkbox',
//            self::FIELD_FILE => 'File',
//            self::FIELD_IMAGE => 'Image',
//            self::FIELD_MULTI_FILE => 'Multiple files',
//
//
//        ];
    }//

    /**
     * Get array of available filter types
     * @return array
     */
    static function getAvailableFilterTypes()
    {
        $types = [];
        foreach (self :: $controls as $control)
        {
            if (!empty($control['filtrable']))
            {
                $types[$control['type']] = $control['caption'] ?? "---";
            }
        }
        return $types;
//        return [
//            self::FIELD_TEXT => 'Text input',
//            self::FIELD_RANGE => 'Number range',
//            self::FIELD_DATE_RANGE => 'Date range',
//            self::FIELD_SELECT => 'Select',
//            self::FIELD_CHECKBOX => 'Checkbox',
//            //self::FIELD_FILE => 'File',
//            //self::FIELD_MULTI_FILE => 'Multiple files',
//        ];
    }//

    static function getAvailableRelationFieldTypes($multiple)
    {
        $ret =  [
            //Form::FIELD_SELECT => 'Select',
            "select" => 'Select',
        ];
        if ($multiple)
        {
//            $ret[Form::FIELD_TAGS] = 'Tags';
//            $ret[Form::FIELD_TREE] = 'Tree';
            $ret['tags'] = 'Tags';
            $ret['tree'] = 'Tree';
        }

        return $ret;
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