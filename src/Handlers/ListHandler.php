<?php namespace Skvn\Crud\Handlers;

use Skvn\Crud\Models\CrudModel;
use Illuminate\Container\Container;
use Skvn\Crud\Filter\Filter;


class ListHandler {


    protected $model;
    protected $app;
    protected $default_options = [
        "title" => "", "description" => "", "multiselect" => "0",
        "buttons" => ["single_edit" => "0", "single_delete" => "0", "mass_delete" => "0", "customize_columns" => "0"],
        "list" => [],
        "searchable" => 0
    ];
    protected $default_column = ["title" => "", "width" => "", "orderable" => "0", "searchable" => "0", "filterable" => "0", "invisible" => "0", "data" => ""];
    protected $columns = [];
    protected $all_columns = [];
    protected $filter;

    public function __construct(CrudModel $parentInstance, $options=[])
    {
        $this->app = Container :: getInstance();
        $this->model = $parentInstance;
        $this->options = array_merge($this->default_options, $options);
        $this->columns = $options['list'] ?? [];
        if (!empty($this->options['multiselect']))
        {
            $this->prependColumn(['data' => $this->model->getKeyName(), 'width' => 30, 'ctyle' => "checkbox"]);
        }
        else
        {
            $this->prependColumn(['data' => $this->model->getKeyName(), 'invisible' => true]);
        }
        if (!empty($this->options['buttons']['single_edit']) || !empty($this->options['buttons']['single_delete']) || !empty($this->options['list_actions']))
        {
            $this->appendColumn(['data' => "actions", 'width' => 100, 'ctype' => "actions"]);
        }
        foreach($this->columns as $k => $col)
        {
            if (empty($col['title']))
            {
                $cdesc = $this->model->getColumn($col['data']);
                if (!empty($cdesc['title'])) {
                    $this->columns[$k]['title'] = $cdesc['title'];
                }
            }
            if (!empty($col['hint']) && empty($col['hint']['index']))
            {
                $this->columns[$k]['hint']['index'] = $this->model->classViewName.'_'.$this->model->scope.'_'.$col['data'];
            }
            if (empty($col['hint']) && !empty($col['hint_default']))
            {
                $this->columns[$k]['hint'] = [
                    'index' => $this->model->classViewName.'_'.$this->model->scope.'_'.$col['data'],
                    'default' => $col['hint_default']
                ];
            }
            if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
            {
                unset($this->columns[$k]);
            }
        }
        if ($this->app['auth']->check())
        {
            $user = $this->app['auth']->user();
            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
            {
                $cols = $user->crudPrefFilterTableColumns($this->columns, $this->model);
                foreach($this->columns as $col)
                {
                    if (!empty($col['invisible']))
                    {
                        $cols[] = $col;
                    }
                }
                $this->all_columns = $this->columns;
                $this->columns = $cols;
            }
        }


    }

    static function create(CrudModel $parentInstance, $options = [])
    {
        return new self($parentInstance, $options);
    }

    function appendColumn($col)
    {
        $this->columns[] = array_merge($this->default_column, $col);
        return $this;
    }

    function prependColumn($col)
    {
        array_unshift($this->columns, array_merge($this->default_column, $col));
        return $this;
    }

    function setOption($opt, $value)
    {
        $this->options[$opt] = $value;
        return $this;
    }

    function getOption($opt, $default = null)
    {
        return $this->options[$opt] ?? $default;
    }

//    public  function appendConditions($conditions)
//    {
//        return $conditions;
//    }
//
//    public  function preApplyConditions($coll, $conditions)
//    {
//        return $conditions;
//    }

    public function initFilter()
    {
        $filter =  Filter::create($this->model, $this->model->getScope());
        $this->setFilter($filter);
        return $this;
    }

    public function setFilter(Filter $filterObj)
    {
        $this->filter = $filterObj;
        $this->filter->setModel($this->model);
        return $this;
    }

    public  function getFilter()
    {
        if (!$this->filter)
        {
            throw new \InvalidArgumentException("Filter object is not set");
        }
        return $this->filter;
    }

    public function getFilterColumns()
    {
        return $this->filter->filters;
    }

    public function fillFilter($scope, $input)
    {
        $this->initFilter($scope);

        return $this->filter->fill($input, true);
    }


    function getDefaultFilter()
    {
        if (!empty($this->options['filter_default']))
        {
            return $this->options['filter_default'];
        }

        return [];
    }

    function getParam($prop = '')
    {
        if (strpos($prop,'.') === false)
        {
            if (empty($prop))
            {
                return array_merge($this->options, ['list' => $this->columns, 'all_columns' => $this->all_columns]);
            }
            else
            {
                switch ($prop)
                {
                    case 'list':
                    case 'columns':
                        return $this->columns;
                    break;
                    case 'all_columns':
                        return $this->all_columns;
                    break;
                    default:
                        return $this->getOption($prop);
                    break;
                }
            }
        }
        else
        {
            return $this->app['config']->get('crud.'.$this->model->getTable().'.scopes.'.$this->model->scope.'.'.$prop);
        }

    }





}