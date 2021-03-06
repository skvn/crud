<?php

namespace Skvn\Crud\Form;

use Illuminate\Container\Container;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudStubModel;

class Form
{
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

    /**
     * Form constructor.
     *
     * @param array $args
     */
    public function __construct($args = [])
    {
        $this->crudObj = isset($args['crudObj']) ? $args['crudObj'] : new CrudStubModel();
        $this->customProperties = isset($args['props']) ? $args['props'] : [];
        if (! empty($args['fields']) && is_array($args['fields'])) {
            $this->setFields($args['fields']);
        }
        if (! empty($args['data']) && is_array($args['data'])) {
            $this->import($args['data']);
        }
        if (! empty($args['tabs'])) {
            $this->setTabs($args['tabs']);
        }
    }

//

    public static function registerControl($class)
    {
        $control = $class :: create();
        if (! $control instanceof FormControl) {
            throw new ConfigException('Invalid control class '.$class);
        }
        if (isset(self :: $controls[$control->controlType()])) {
            throw new ConfigException('Control already registered: '.$class);
        }
        self :: $controls[$control->controlType()] = $control;
    }

    public static function getAvailControls()
    {
        return self :: $controls;
    }

    public static function getAvailControl($type)
    {
        if (! isset(self :: $controls[$type])) {
            throw new ConfigException('Invalid control `'.$type.'`');
        }

        return self :: $controls[$type];
    }

    public static function create($args = [])
    {
        return new self($args);
    }

    public static function createControl(CrudModel $model, $config)
    {
        $class = get_class(self :: $controls[$config['type']]);

        return $class :: create()->setConfig($config)->setModel($model);
    }

//    static function configureModel(CrudModel $model, $config)
//    {
//        self :: $controls[$config['type']]->configureModel($model, $config);
//    }

//    static function configureModel(CrudModel $model, $config)
//    {
//        $class = get_class(self :: $controls[$config['type']]);
//        $class :: configureModel($model)
//    }

    public function addField($name, $config, $tab = null)
    {
        if (empty($config['name'])) {
            $config['name'] = $name;
        }
        if (! empty($tab)) {
            $config['tab'] = $tab;
        }
        $this->config[$name] = $config;
        $this->fields[$name] = self :: createControl($this->crudObj, $config);
        if (isset($config['value'])) {
            $this->fields[$name]->setValue($config['value']);
        }
//        if (!$this->crudObj->getField($name))
//        {
//            $this->crudObj->addFormField($name, isset($config['title']) ? $config['title'] : '', isset($config['type']) ? $config['type'] : "text", $config);
//        }
        return $this;
    }

    public function setFields($fields)
    {
        $this->fields = [];
        foreach ($fields as $col => $colConfig) {
            $this->addField($col, $colConfig);
        }

        return $this;
    }

    public function setCustomProperties($props)
    {
        $this->customProperties = $props;

        return $this;
    }

    public function addTab($tab_index, $tab)
    {
        $this->tabs[$tab_index] = $tab;
    }

    public function setTabs($tabs)
    {
        $this->tabs = $tabs;

        return $this;
    }

    public function hasTabs()
    {
        return ! empty($this->tabs);
    }

    public function import($data)
    {
        foreach ($this->fields as $field) {
            if (empty($field->config['disabled'])) {
                $field->pullFromData($data);
            }
        }
        foreach ($this->crudObj->getHiddenFields() as $f) {
            if (isset($data[$f])) {
                $this->crudObj->$f = $data[$f];
            }
        }

        return $this;
    }

    public function load($data)
    {
        $this->import($data);
        $this->sync();
    }

    public function sync()
    {
        foreach ($this->fields as $field) {
            if (! empty($field->config['acl']) && ! Container :: getInstance()['skvn.cms']->checkAcl($field->config['acl'], 'u')) {
                continue;
            }
            if (empty($field->config['disabled'])) {
                $field->pushToModel();
            }
        }
    }

    public function __toString()
    {
        $app = Container :: getInstance();
        $res = $app['view']->make('crud::crud/form', ['crudObj' => $this->crudObj])->render();

        return (string) $res;
    }

    /**
     * Return One field object by it's field name.
     *
     * @param $fieldName
     *
     * @return array
     */
    public function getFieldByName($fieldName)
    {
        return $this->fields[$fieldName];
    }

    /**
     * Return class instance of the control by it's TYPE constant.
     *
     * @param string $type
     *
     * @return FormControl|null
     */
    public static function getControlByType(string $type)
    {
        return self::$controls[$type] ?? null;
    }
}
