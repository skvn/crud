<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Form\FieldFactory;
use Skvn\Crud\Exceptions\ConfigException;


trait ModelConfigTrait
{

    protected $config;
    public $classShortName;
    public $classViewName;
    public $scope = "default";

    protected $crudRelations = [];
    protected $processableRelations = [];
    protected $list_prefs = null;

    /* Flag for tracking created_by  and updated_by */
    protected $track_authors = false;


    protected function initConfig()
    {
        $this->classShortName = class_basename($this);
        $this->classViewName = snake_case($this->classShortName);
        $this->config = $this->app['config']->get('crud.crud_'.$this->getTable());
        $this->config['class_name'] = $this->classViewName;
        if (!empty($this->config['fields']))
        {
            //FIXME
            if (!isset($this->config['form']))
            {
                $this->config['form'] = array_keys($this->config['fields']);
            }
            foreach ($this->config['fields'] as $name=>  $col)
            {

                if (!empty($col['hint_default']) && !empty($col['hint']) &&  $col['hint'] === 'auto')
                {
                    $col['hint'] = $this->classShortName.'_fields_'.$name;
                }
                //fill relations
                if (isset($col['relation']))
                {
                    $rel_name = $this->getRelationNameByColumnName($name);
                    $this->crudRelations[$rel_name] = $col['relation'];
                }

                //if field in form - make it fillable or processable as relation
                if (in_array($name,$this->config['form']) || !empty($col['fillable']))
                {
                    if (isset($col['relation']) &&
                        ($col['relation'] == 'belongsToMany' || $col['relation'] == 'hasMany' || $col['relation'] == 'hasOne')

                    ) {
                        if ($col['relation'] != 'hasOne')
                        {
                            $this->config['fields'][$name]['multiple'] = 1;
                        }

                        //Add multi file to fillable since it is handled by fill not by post save relations
                        if ($col['type'] != 'multi_file')
                        {
                            $this->processableRelations[$name] = $col['relation'];
                        }
                        else
                        {
                            $this->fillable[] = $name;
                        }
                        continue;

                    }
                    else
                    {
                        if (!empty($col['fields']))
                        {
                            foreach ($col['fields'] as $f)
                            {
                                $this->fillable[] = $f;
                            }
                        }
                        else
                        {
                            $this->fillable[] = $name;
                        }
                    }
                }
            }
        }

        if (empty($this->table))
        {
            if (!isset($this->config['table']))
            {
                $this->table = $this->classViewName;
            }
            else
            {
                $this->table = $this->config['table'];
            }
        }
        if (isset($this->config['timestamps']))
        {
            $this->timestamps = $this->config['timestamps'];
        }

        if (isset($this->config['authors']))
        {
            $this->track_authors = $this->config['authors'];
        }

        if ($this->isTree())
        {
//            $this->fillable[] = $this->columnTreePid;
//            $this->fillable[] = $this->columnTreeOrder;
//            $this->fillable[] = $this->columnTreePath ;
//            $this->fillable[] = $this->columnTreeDepth;
            $this->fillable[] = $this->config['tree']['pid_column'];
            $this->fillable[] = $this->config['tree']['order_column'];
            $this->fillable[] = $this->config['tree']['path_column'] ;
            $this->fillable[] = $this->config['tree']['depth_column'];
        }


    }

//    function objectifyConfig()
//    {
//        $conf = new \Skvn\Crud\CrudConfig($this);
//        $c = $this->config;
//        $c['list'] = $this->getListConfig();
//
//        if (!empty($c['list']['multiselect']))
//        {
//            array_unshift($c['list']['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
//        }
//
//        if (!empty($c['list']['buttons']['single_edit'])
//            || !empty($c['list']['buttons']['single_delete'])
//            || !empty($c['list']['list_actions'])
//
//        )
//        {
//            $c['list']['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
//        }
//
//        foreach($c['list']['columns'] as $k=>$col)
//        {
//            if (empty($col['title']))
//            {
//                $cdesc = $this->getColumn($col['data']);
//                if (!empty($cdesc['title'])) {
//                    $c['list']['columns'][$k]['title'] = $cdesc['title'];
//                }
//            }
//            if (!empty($col['hint']) && empty($col['hint']['index']))
//            {
//                $c['list']['columns'][$k]['hint']['index'] = $this->classViewName.'_'.$this->scope.'_'.$col['data'];
//            }
//            if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
//            {
//                unset($c['list']['columns'][$k]);
//            }
//        }
//        //$c['filter'] = $this->getFilter();
//        if ($this->app['auth']->check())
//        {
//            $user = $this->app['auth']->user();
//            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
//            {
//                $cols = $user->crudPrefFilterTableColumns($c['list']['columns'], $this);
//                foreach($c['list']['columns'] as $col)
//                {
//                    if (!empty($col['invisible']))
//                    {
//                        $cols[] = $col;
//                    }
//                }
//                $c['list']['columns'] = $cols;
//            }
//        }
//
//        if (!empty($c['list']['list_actions'])) {
////            $actions = [];
////            foreach ($this->config['list']['list_actions'] as $action) {
////                $actions[] = $action['title'].'|'.$action['command'].(isset($action['class'])?'|'.$action['class']:'');
////            }
////            $this->config['list_actions'] = implode(',',$actions);
//            $c['list_actions'] = json_encode($c['list']['list_actions']);
//        } else {
//            $c['list_actions'] = "";
//        }
//
//
//        $c['list_name'] = $this->getListName();
//        $c['scope'] = $this->scope;
//        $conf->setConfig($c);
//        return $conf;
//
//    }

    public function getFields($prop='')
    {
        $form =  $this->confParam('fields');
        if (empty($prop))
        {
            return $form;
        }
        else
        {
            if (!empty($form[$prop]))
            {
                return $form[$prop];
            }
        }
    }

    /**
     * @param $key
     * @param null $default
     * @param bool $use_scope
     * @return mixed
     *
     * Get config param. If $use_scope === true, look first in list.CURRENT_SCOPE
     *
     */
    public function confParam($key, $default = null, $use_scope=false)
    {
        if (!$use_scope) {
            if (strpos($key, '.') === false) {
                return (!empty($this->config[$key]) ? $this->config[$key] : $default);
            } else {
                return $this->app['config']->get('crud.crud_' . $this->getTable() . '.' . $key, $default);
            }
        } else {

            $original_key = $key;
            $key = $key.'.list.'.$this->scope;
            $val = $this->app['config']->get('crud.crud_' . $this->getTable() . '.' . $key, null);
            if (is_null($val))
            {
                return $this->confParam($original_key, $default, false);
            }
        }
    }

    public function getColumn($col, $scope='fields')
    {
        if (!empty($this->config[$scope][$col]))
        {
            $conf =  $this->config[$scope][$col];
            $conf['column_index'] = $col;
        }
        else
        {
            $conf =  $this->resolveColumnByRelationName($col, $scope);
        }

        return $conf;
    }

    public function getFormConfig($prop='')
    {
        $form = null;
        $form =  $this->confParam('form');
        $tabbed = $this->confParam('form_tabbed');

//        if (!empty($this->scope))
//        {
//            $form = $this->getListConfig("form");
//        }
//        if (!$form)
//        {
//            $form =  $this->confParam('form');
//        }

        $form_array = [];
        $fields = $this->getFields();

        if (is_array($form))
        {
            if ($tabbed)
            {
                foreach ($form as $tab_alias=>$field_set) {
                    foreach ($field_set as $fname)
                    {
                        $form_array[$fname] = $fields[$fname];
                        $form_array[$fname]['tab'] = $tab_alias;
                    }

                }

            } else {

                foreach ($form as $fname) {
                    $form_array[$fname] = $fields[$fname];
                }
            }

        }

        if (empty($prop))
        {
            return $form_array;
        }
        else
        {
            return $form_array[$prop];
        }
    }

    public function getListConfig($prop='')
    {

        if (strpos($prop,'.') === false)
        {
            $conf = $this->confParam('list.' . $this->scope);
            if (empty($conf))
            {
                $conf = [];
            }
            if (empty($conf['columns']))
            {
                $conf['columns'] = [];
            }
            if (!empty($conf['multiselect']))
            {
                array_unshift($conf['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
            }
            if (!empty($conf['buttons']['single_edit'])
                || !empty($conf['buttons']['single_delete'])
                || !empty($conf['list_actions'])

            )
            {
                $conf['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
            }

            foreach($conf['columns'] as $k=>$col)
            {

                if (empty($col['title']))
                {
                    $cdesc = $this->getColumn($col['data']);
                    if (!empty($cdesc['title'])) {
                        $conf['columns'][$k]['title'] = $cdesc['title'];
                    }
                }
                if (!empty($col['hint']) && empty($col['hint']['index']))
                {
                    $conf['columns'][$k]['hint']['index'] = $this->classViewName.'_'.$this->scope.'_'.$col['data'];
                }
                if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
                {
                    unset($conf['columns'][$k]);
                }
            }

            if ($this->app['auth']->check())
            {
                $user = $this->app['auth']->user();
                if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
                {
                    $cols = $user->crudPrefFilterTableColumns($conf['columns'], $this);
                    foreach($conf['columns'] as $col)
                    {
                        if (!empty($col['invisible']))
                        {
                            $cols[] = $col;
                        }
                    }
                    $conf['columns'] = $cols;
                }
            }



            if (empty($prop))
            {
                return $conf;
            }
            else
            {
                if (isset($conf[$prop]))
                {
                    return $conf[$prop];
                }
            }
        }
        else
        {
            return $this->app['config']->get('crud.crud_'.$this->getTable().'.list.'.$this->scope.'.'.$prop);
        }

    }

    function getTreeConfig($prop = '')
    {
        if (isset($this->config['tree']))
        {
            if (!empty($prop))
            {
                if (isset($this->config['tree'][$prop]))
                {
                    return $this->config['tree'][$prop];
                }
            }
            else
            {
                return $this->config['tree'];
            }
        }
        return false;
    }


    function isTree()
    {
        return !empty($this->config['tree']) && !$this->getTreeConfig('use_list');
    }

    function getDescribedColumnValue($col)
    {
        if ($relSpl = $this->resolveListRelation($col))
        {
            $rel = $relSpl[0];
            $attr = $relSpl[1];
            return $this->$rel->$attr;
        }
        else
        {
            if ($this->__isset($col))
            {
                $form_config = $this->getFields($col);
                if ($form_config && !empty($form_config['type']))
                {
                    $form_config['name'] = $col;
                    $field = FieldFactory::create($this->getForm(), $form_config);
                    return $field->getValueForList();
                }
                return $this->$col;
            }
            else
            {
                $meth = camel_case('get_' . $col);
                if (method_exists($this, $meth))
                {
                    return $this->$meth();
                }
            }
        }
    }


    public function setScope($scope = null)
    {
        if (is_null($scope) || $scope == 'null')
        {
            $this->scope = self :: DEFAULT_SCOPE;
        }
        else
        {
            $this->scope = $scope;
        }
        if (!isset($this->config['list'][$this->scope]))
        {
            throw new ConfigException('Scope ' . $this->scope . ' for model ' . $this->config['class_name'] . ' not found');
        }
        $this->config['scope'] = $this->scope;
    }

    function getScope()
    {
        return $this->scope;
    }

    public  function getListName()
    {
        return $this->scope ? $this->scope : self :: DEFAULT_SCOPE;
    }

    public function getFilterConfig($prop='')
    {
        //$form = $this->confParam('list.' . $this->scope . '.filter');
        //FIXME: backward compability
        $form = $this->getListConfig("filters");
        if (empty($form))
        {
            $form = [];
            foreach ($this->getListConfig("columns") as $column)
            {
                if (!empty($column['filterable']))
                {
                    $form[] = $column['data'];
                }
            }
        }

        //FIXME: WTF ?
        if (empty($prop))
        {
            return $form;
        }
        else
        {
            return $form[$prop];
        }
    }

    function isColumnVisible($column)
    {
        if (is_null($this->list_prefs))
        {
            $this->list_prefs = false;
            if ($this->app['auth']->check())
            {
                $user = $this->app['auth']->user();
                if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
                {
                    $this->list_prefs = $user->crudPrefForModel(constant(get_class($user) . "::PREF_TYPE_COLUMN_LIST"), $this);
                }
            }
        }
        if (empty($this->list_prefs))
        {
            return true;
        }
        if (empty($this->list_prefs['columns']))
        {
            return true;
        }
        return in_array($column, $this->list_prefs['columns']);
    }

    function isManyRelation($relation)
    {
        return in_array($relation, ['hasMany','belongsToMany', 'morphToMany', 'morphedByMany']);
    }


    function resolveView($view)
    {
        $hints = $this->app['view']->getFinder()->getHints();
        $key = "crud." . $this->classViewName . "." . $this->getScope();
        $source = isset($hints[$key]) ? $hints[$key] : [];
        if (empty($source))
        {
            $target = [];
            $add = [
                '/crud',
                '/crud/models',
                '/crud/models/' . $this->classViewName,
                '/crud/models/' . $this->classViewName . '/' . $this->getScope(),
            ];
            foreach ($this->app['config']['view.paths'] as $path)
            {
                if (isset($hints['crud']))
                {
                    foreach ($hints['crud'] as $entry)
                    {
                        if (!in_array($entry, $target))
                        {
                            $target[] = $entry;
                        }
                    }
                }
                if (!in_array($path, $target))
                {
                    $target[] = $path;
                }
                foreach ($add as $entry)
                {
                    $tpath = $path . $entry;
                    if (!in_array($tpath, $source))
                    {
                        array_unshift($target, $tpath);
                    }
                }
            }
            if (!empty($target))
            {
                $this->app['view']->getFinder()->prependNamespace($key, $target);
            }
        }
        return $key . "::" . $view;
    }



}