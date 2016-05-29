<?php namespace Skvn\Crud\Form;

use Illuminate\Container\Container;
use Skvn\Crud\Models\CrudStubModel;


/**
 * Class Form
 * @package Skvn\Crud
 * @author  Vitaly Nikolenko <vit@webstandart.ru>
 */
class Form {

    /**
     *
     */
    const FIELD_SELECT = 'select';
    /**
     *
     */
    const FIELD_TEXT = 'text';
    /**
     *
     */
    const FIELD_FILE = 'file';
    const FIELD_IMAGE = 'image';
    /**
     *
     */
    const FIELD_CHECKBOX = 'checkbox';
    /**
     *
     */
    const FIELD_MULTI_FILE = 'multi_file';
    /**
     *
     */
    const FIELD_TEXTAREA = 'textarea';
    /**
     *
     */
    const FIELD_DATE = 'date';

    /**
     *
     */
    const FIELD_DATE_TIME = 'date_time';

    /**
     *
     */
    const FIELD_RANGE = 'range';
    /**
     *
     */
    const FIELD_DATE_RANGE = 'date_range';
    /**
     *
     */
    const FIELD_NUMBER = 'number';

    /**
     *
     */
    const FIELD_DECIMAL = 'decimal';

    /**
     *
     */
    const FIELD_TAGS = 'tags';

    /**
     *
     */
    const FIELD_TREE = 'tree';




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
    public $fields;

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
            foreach ($args['config'] as $col => $colConfig)
            {
                $this->addControl($col, $colConfig);
            }
        }
        if (!empty($args['data']) && is_array($args['data']))
        {
            $this->import($args['data']);
        }


//        if (is_array($this->config)) {

//            foreach ($this->config as $col => $colConfig)
//            {
//                if (empty($colConfig['column']))
//                {
//                    $colConfig['column'] = $col;
//                }
//                if (empty($colConfig['name']))
//                {
//                    $colConfig['name'] = $col;
//                }
//                $this->fields[$col] = Field::create($this->crudObj, $colConfig);
//                if (!empty($args['data']))
//                {
//                    $this->fields[$col]->importValue($args['data']);
//                    switch ($colConfig['type'])
//                    {
//                        case self::FIELD_RANGE:

//                            if (!empty($args['data'][$col]) && strpos($args['data'][$col],'~') !== false)
//                            {
//                                $this->fields[$col]->setValue($args['data'][$col]);
//
//                            }
//                            else
//                            {
//                                if (isset($args['data'][$col . '_from']) || isset ($args['data'][$col . '_to']))
//                                {
//                                    $from = 0;
//                                    $to = 0;
//                                    if (isset($args['data'][$col . '_from']))
//                                    {
//                                        $from = $args['data'][$col . '_from'];
//                                    }
//                                    if (isset($args['data'][$col . '_to']))
//                                    {
//                                        $to = $args['data'][$col . '_to'];
//                                    }
//                                    $this->fields[$col]->setValue($from . '~' . $to);
//                                }
//                            }
//                            break;
//                        case self::FIELD_DATE_RANGE:
//                            if (!empty($args['data'][$col]) && strpos($args['data'][$col],'~') !== false)
//                            {
//                                $this->fields[$col]->setValue($args['data'][$col]);
//                            }
//                            else
//                            {
//                                if (isset($args['data'][$col . '_from']) || isset ($args['data'][$col . '_to']))
//                                {
//                                    $from = 0;
//                                    $to = '';
//                                    if (isset($args['data'][$col . '_from']))
//                                    {
//                                        $from = strtotime($args['data'][$col . '_from']);
//                                    }
//                                    if (isset($args['data'][$col . '_to']))
//                                    {
//                                        $to = strtotime($args['data'][$col . '_to']);
//                                    }
//                                    $this->fields[$col]->setValue($from . '~' . $to);
//                                }
//                            }
//                            break;
//                        default:
//                            if (isset($args['data'][$col]))
//                            {
//                                $this->fields[$col]->setValue($args['data'][$col]);
//                            }
//                            break;
//                    }
//                }
//                else
//                {
//                    if (isset($colConfig['value']))
//                    {
//                        $this->fields[$col]->setValue($colConfig['value']);
//                    }
//                }
//            }
//        }

    }//

    static function create($args = [])
    {
        return new self($args);
    }

    function addControl($name, $config)
    {
        if (empty($config['column']))
        {
            $config['column'] = $name;
        }
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
            $this->crudObj->addFormField($name, isset($config['title']) ? $config['title'] : '', isset($config['type']) ? $config['type'] : self :: FIELD_TEXT, $config);
        }
    }

    function import($data)
    {
        foreach ($this->fields as $field)
        {
            $field->importValue($data);
        }
    }

    function load($data)
    {
        $this->import($data);
        $this->crudObj->fillFromRequest($data);
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

//    public function getFieldsAsHtml()
//    {
//        $app = Container :: getInstance();
//        return $app['view']->make('crud::crud/fields', ['crudObj'=>$this->crudObj])->render();
//    }

    /**
     * Get array of available edit types
     * @return array
     */
    static function getAvailableFieldTypes()
    {
        return [
            self::FIELD_TEXT => 'Text input',
            self::FIELD_NUMBER => 'Number input',
            self::FIELD_TEXTAREA => 'Textarea',
            self::FIELD_DATE => 'Date',
            self::FIELD_DATE_TIME => 'Date + Time',
            self::FIELD_DATE_RANGE => 'Date range',
            self::FIELD_SELECT => 'Select',
            self::FIELD_CHECKBOX => 'Checkbox',
            self::FIELD_FILE => 'File',
            self::FIELD_IMAGE => 'Image',
            self::FIELD_MULTI_FILE => 'Multiple files',


        ];
    }//

    /**
     * Get array of available filter types
     * @return array
     */
    static function getAvailableFilterTypes()
    {
        return [
            self::FIELD_TEXT => 'Text input',
            self::FIELD_RANGE => 'Number range',
            self::FIELD_DATE_RANGE => 'Date range',
            self::FIELD_SELECT => 'Select',
            self::FIELD_CHECKBOX => 'Checkbox',
            //self::FIELD_FILE => 'File',
            //self::FIELD_MULTI_FILE => 'Multiple files',
        ];
    }//

    static function getAvailableRelationFieldTypes($multiple)
    {
        $ret =  [
            Form::FIELD_SELECT => 'Select',
        ];
        if ($multiple)
        {
            $ret[Form::FIELD_TAGS] = 'Tags';
            $ret[Form::FIELD_TREE] = 'Tree';
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