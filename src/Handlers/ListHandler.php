<?php

namespace Skvn\Crud\Handlers;

use Illuminate\Container\Container;
use Skvn\Crud\Filter\Filter;
use Skvn\Crud\Models\CrudModel;

class ListHandler
{
    public $model;
    public $ref_id;
    public $scope;
    public $modelClass;

    protected $app;
    protected $default_options = [
        'title'      => '', 'description' => '', 'multiselect' => '0',
        'buttons'    => ['single_edit' => '0', 'single_delete' => '0', 'mass_delete' => '0', 'customize_columns' => '0'],
        'list'       => [],
        'searchable' => 0,
    ];
    protected $default_column = ['title' => '', 'width' => '', 'orderable' => '0', 'searchable' => '0', 'filterable' => '0', 'invisible' => '0', 'data' => ''];
    protected $columns = [];
    protected $all_columns = [];
    protected $filter = null;
    protected $user_prefs = null;

    public function __construct(CrudModel $parentInstance, $options = [])
    {
        $this->app = Container :: getInstance();
        $this->model = $parentInstance;
        $this->ref_id = $this->model->classViewName.'_'.$this->model->getScope();
        $this->scope = $this->model->getScope();
        $this->modelClass = $this->model->classViewName;

        $this->options = $this->prepareOptions($options);
        $this->columns = $options['list'] ?? [];
        $this->loadPrefs();
        if (! empty($this->options['multiselect'])) {
            $this->prependColumn(['data' => $this->model->getKeyName(), 'width' => 30, 'ctype' => 'checkbox']);
        } else {
            $this->prependColumn(['data' => $this->model->getKeyName(), 'invisible' => true]);
        }
        if (! empty($this->options['buttons']['single_edit']) || ! empty($this->options['buttons']['single_delete'])
            || ! empty($this->options['list_single_actions'])) {
            $this->appendColumn(['data' => 'actions', 'width' => 100, 'ctype' => 'actions']);
        }
        foreach ($this->columns as $k => $col) {
            if (empty($col['title'])) {
                $cdesc = $this->model->getField($col['data']);
                if (! empty($cdesc['title'])) {
                    $this->columns[$k]['title'] = $cdesc['title'];
                }
            }
            if (! empty($col['hint']) && empty($col['hint']['index'])) {
                $this->columns[$k]['hint']['index'] = $this->model->classViewName.'_'.$this->model->scope.'_'.$col['data'];
            }
            if (empty($col['hint']) && ! empty($col['hint_default'])) {
                $this->columns[$k]['hint'] = [
                    'index'   => $this->model->classViewName.'_'.$this->model->scope.'_'.$col['data'],
                    'default' => $col['hint_default'],
                ];
            }
            if (! empty($col['acl']) && ! $this->app['skvn.cms']->checkAcl($col['acl'], 'r')) {
                unset($this->columns[$k]);
            }
        }
        $cols = $this->filterColumns();
        foreach ($this->columns as $col) {
            if (! empty($col['invisible'])) {
                $cols[] = $col;
            }
        }
        $this->all_columns = $this->columns;
        $this->columns = $cols;
    }

    public static function create(CrudModel $parentInstance, $options = [])
    {
        return new self($parentInstance, $options);
    }

    protected function filterColumns()
    {
        $cols = [];
        foreach ($this->columns as $column) {
            if (! empty($column['ctype']) || $this->isColumnVisible($column['data'])) {
                $cols[] = $column;
            }
        }
        if (empty($cols)) {
            return $this->columns;
        }

        return $cols;
    }

    public function appendColumn($col)
    {
        $this->columns[] = array_merge($this->default_column, $col);

        return $this;
    }

    public function prependColumn($col)
    {
        array_unshift($this->columns, array_merge($this->default_column, $col));

        return $this;
    }

    public function setOption($opt, $value)
    {
        $this->options[$opt] = $value;

        return $this;
    }

    public function getOption($opt, $default = null)
    {
        return $this->options[$opt] ?? $default;
    }

    public function getFilter()
    {
        if (! $this->filter) {
            $cols = [];
            foreach ($this->columns as $column) {
                if (! empty($column['filterable'])) {
                    $rel = $this->model->crudRelations->resolveReference($column['data']);
                    if ($rel !== false) {
                        $column['data'] = $rel['rel'];
                    }
                    $cols[$column['data']] = $column['data'];
                    if ($fld = $this->model->getField($column['data'], true)) {
                        if (! empty($fld['field'])) {
                            $cols[$column['data']] = $fld['field'];
                        }
                    }
                }
            }
            foreach ($this->options['filter'] ?? [] as $column) {
                if (! array_key_exists($column, $cols) && $fld = $this->model->getField($column)) {
                    $cols[$column] = $fld['field'];
                }
            }
            $this->filter = Filter :: create([
                'model'    => $this->model,
                'defaults' => $this->options['filter_default'] ?? [],
                'filters'  => $cols,
            ])->fill();
//            $this->filter = Filter :: create()
//                            ->setModel($this->model)
//                            ->setDefaults($this->options['filter_default'] ?? [])
//                            ->setFilters($cols)
//                            ->fill();
        }

        return $this->filter;
    }

    public function hasFilter()
    {
        return ! empty($this->getFilter()->filters);
    }

    public function fillFilter($input)
    {
        return $this->getFilter()->fill($input);
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getParam($prop = '')
    {
        if (empty($prop)) {
            return array_merge($this->options, ['list' => $this->columns, 'all_columns' => $this->all_columns]);
        }
        if (strpos($prop, '.') !== false) {
            list($s, $p) = explode('.', $prop, 2);
            if (! isset($this->options[$s])) {
                return;
            }

            return $this->options[$s][$p] ?? null;
        }
        switch ($prop) {
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

//

    private function prepareOptions($options)
    {
        $combined_options = array_merge($this->default_options, $options);
        if (! empty($combined_options['list_actions'])) {
            array_walk($combined_options['list_actions'], function (&$act, $idx) {
                switch ($act) {
                    case ! empty($act['popup']):
                        $act['href'] = $act['popup'];
                        $act['click'] = 'crud_popup';
                    break;
                    case ! empty($act['command']):
                        $act['href'] = $act['command'];
                        $act['click'] = 'crud_action';
                        $act['action'] = 'crud_command';
                    break;
                    case ! empty($act['event']):
                        $act['href'] = '#';
                        $act['click'] = 'crud_event';
                    break;
                    default:
                        $act['href'] = '#';
                    break;
                }
            });
            $combined_options['list_single_actions'] = array_filter($combined_options['list_actions'], function ($item) {
                return ! empty($item['single']);
            });

            $combined_options['list_mass_actions'] = array_filter($combined_options['list_actions'], function ($item) {
                return ! empty($item['mass']);
            });
        }

        return $combined_options;
    }

    protected function loadPrefs()
    {
        $this->user_prefs = false;
        if ($this->app['auth']->check()) {
            $user = $this->app['auth']->user();
            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject) {
                $this->user_prefs = $user->crudPrefForModel(constant(get_class($user).'::PREF_TYPE_COLUMN_LIST'), $this->model);
            }
        }
    }

    public function isColumnVisible($column)
    {
        if (is_null($this->user_prefs)) {
            $this->loadPrefs();
        }
        if (empty($this->user_prefs)) {
            return true;
        }
        if (empty($this->user_prefs['columns'])) {
            return true;
        }

        return in_array($column, $this->user_prefs['columns']);
    }
}
