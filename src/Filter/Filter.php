<?php namespace Skvn\Crud\Filter;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Form\Form;
use Skvn\Crud\Form\Field;
use Skvn\Crud\Contracts\FormControlFilterable;
use Illuminate\Container\Container;
use Skvn\Crud\Exceptions\Exception;

class Filter {



    public $filters = [];
    protected $model;
    protected $form;
    protected $app;
    protected $defaults = [];

    public function __construct()
    {
        $this->app = Container :: getInstance();
    }

    static function create($args = [])
    {
        $filter = new self();
        foreach ($args as $k => $v)
        {
            $method = camel_case('set_' . $k);
            if (!method_exists($filter, $method))
            {
                throw new Exception("No " . $k . " argument exists for filter " . get_class($filter));
            }
            $filter->$method($v);
        }
        return $filter;

    }

    public function setModel(CrudModel $crudObj)
    {
        $this->model = $crudObj;
        return $this;
    }

    function setDefaults($defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    function addFilter($name, $field = null)
    {
        $col = $this->model->getField($name);
        if (!$col)
        {
            return;
        }
        $control = Form :: getControlByType($col['type']);
        if (!$control instanceof FormControlFilterable)
        {
            return;
        }
        $col['required'] = false;
        $col['name'] = $name;
        $col['field'] = $field ?? $name;
        if ($col['type'] == Field :: SELECT)
        {
            $col['multiple'] = true;
        }
        if (isset($this->defaults[$name]))
        {
            $col['default'] = is_array($this->defaults[$name]) ? implode(",", $this->defaults[$name]) : $this->defaults[$name];
        }
        $filter = Form :: createControl($this->model, $col);
        if (!empty($field) && $field != $name)
        {
            $filter->setFilterColumnName($field);
        }
        $this->filters[] = $filter;
        return $this;
    }

    function setFilters($filters)
    {
        $this->filters = [];
        foreach ($filters as $fname => $ffield)
        {
            $this->addFilter($fname, $ffield);
        }
        return $this;
    }

    function fill($input = [])
    {
        $stored = $this->app['session']->get($this->getStorageKey()) ?? [];
        $store = [];
        $data = array_merge($this->defaults, $stored);
        foreach ($this->filters as $filter)
        {
            $filter->setValue($data[$filter->name] ?? null);
            if (!empty($input))
            {
                $filter->pullFromData($input);
                $store[$filter->name] = $filter->getValue();
            }
        }
        if (!empty($store))
        {
            $this->app['session']->put($this->getStorageKey(), $store);
        }
        return $this;
    }

    public function getStorageKey()
    {
        return 'crud_filter_'.$this->model->classViewName . "_" . $this->model->scope;
    }

    public function getConditions()
    {
        $filters = [];
        foreach ($this->filters as $filter)
        {
            if ($filter instanceof FormControlFilterable)
            {
                $c = $filter->getFilterCondition();
                if ($c)
                {
                    $filters[$filter->getField()] = $c;
                }
            }
        }
        return $filters;
    }

}