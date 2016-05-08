<?php namespace Skvn\Crud;

use JsonSerializable, ArrayAccess;

class CrudConfig implements JsonSerializable, ArrayAccess {









    protected $config;
    protected $model;

    function __construct($obj)
    {
        $this->model = $obj;
    }


    function setConfig($c)
    {
        $this->config = $c;
    }

    function jsonSerialize()
    {
        $this->config['filter'] = $this->model->getFilter();
        return $this->config;
    }

    function getList($param = '')
    {
        var_dump("deprecated");
        return $this->model->getListConfig($param);
    }

    function getScope()
    {
//        if (!\Request :: ajax())
//        {
//            var_dump("deprecated");
//        }
        return $this->model->scope;
    }

    function get($param)
    {
        return $this->model->confParam($param);
    }
//    protected $crudRelations = [];
//    protected $processableRelations = [];
//    protected $fillable = [];
//    protected $manyRelations = array('hasMany','belongsToMany', 'morphToMany', 'morphedByMany');
//    protected $scope = "default";
//    protected $model;
//    protected $list_prefs = null;
//    protected $app;


//    public function __construct($model)
//    {
//        $this->app = app();
//
//        $this->model = $model;
//
//        $this->config =   $this->app['config']->get('crud.crud_'.$model->getTable());
//        $this->config['class_name'] = $model->classViewName;
//
//
//        if (!empty($this->config['fields']))
//        {
//            if (!isset($this->config['form']))
//            {
//                $this->config['form'] = array_keys($this->config['fields']);
//            }
//            foreach ($this->config['fields'] as $name=>  $col)
//            {
//
//                if (!empty($col['hint_default']) && !empty($col['hint']) &&  $col['hint'] === 'auto')
//                {
//                    $col['hint'] = $this->model->classShortName.'_fields_'.$name;
//                }
//                //fill relations
//                if (isset($col['relation']))
//                {
//                    $rel_name = $this->getRelationNameByColumnName($name);
//                    $this->crudRelations[$rel_name] = $col['relation'];
//                }
//
//                //if field in form - make it fillable or processable as relation
//                if (in_array($name,$this->config['form']) || !empty($col['fillable'])) {
//
//                    if (isset($col['relation']) &&
//                        ($col['relation'] == 'belongsToMany' || $col['relation'] == 'hasMany' || $col['relation'] == 'hasOne')
//
//                    ) {
//
//
//                        if ($col['relation'] != 'hasOne') {
//                            $this->config['fields'][$name]['multiple'] = 1;
//                        }
//
//                        //Add multi file to fillable since it is handled by fill not by post save relations
//                        if ($col['type'] != 'multi_file') {
//                            $this->processableRelations[$name] = $col['relation'];
//                        } else {
//                            $this->fillable[] = $name;
//                        }
//                        continue;
//
//                    } else {
//                        $this->fillable[] = $name;
//                    }
//                }
//
//
//            }
//        }
//
//
//    }

//    public function get($key)
//    {
//        if (strpos($key,'.') === false) {
//            return (!empty($this->config[$key]) ? $this->config[$key] : false);
//        } else
//        {
//
//            return $this->app['config']->get('crud.crud_'.$this->model->getTable().'.'.$key);
//
//        }
//    }

//    public function exists($key)
//    {
//
//         return isset($this->config[$key]);
//
//    }

//    public function getCrudRelations()
//    {
//        return $this->crudRelations;
//    }

//    public function getProcessableRelations()
//    {
//        return $this->processableRelations;
//    }

//    public function getFillable()
//    {
//        return $this->fillable;
//    }

//    public function setScope($scope = null)
//    {
//        if (is_null($scope) || $scope == 'null')
//        {
//            $this->scope = self :: DEFAULT_SCOPE;
//        }
//        else
//        {
//            $this->scope = $scope;
//        }
//        if (!isset($this->config['list'][$this->scope]))
//        {
//            throw new ConfigException('Scope ' . $this->scope . ' for model ' . $this->config['class_name'] . ' not found');
//        }
//    }

//    function getScope()
//    {
//        return $this->scope;
//    }

//    public  function getListName()
//    {
//        return $this->scope ? $this->scope : self :: DEFAULT_SCOPE;
//    }

//    public function getList($prop='')
//    {
//
//        if (strpos($prop,'.') === false) {
//
//            $cols = $this->get('list.' . $this->scope);
//
//            if (empty($prop))
//            {
//                return $cols;
//            } else{
//
//                if (isset($cols[$prop])) {
//                    return $cols[$prop];
//                }
//            }
//
//        } else
//        {
//
//            return $this->app['config']->get('crud.crud_'.$this->model->getTable().'.list.'.$this->scope.'.'.$prop);
//
//        }
//
//    }

//    function getTree($prop = '')
//    {
//        if (isset($this->config['tree']))
//        {
//            if (!empty($prop))
//            {
//                if (isset($this->config['tree'][$prop]))
//                {
//                    return $this->config['tree'][$prop];
//                }
//            }
//            else
//            {
//                return $this->config['tree'];
//            }
//        }
//        return false;
//    }

//    public function getForm($prop='')
//    {
//        $form = null;
//        if (!empty($this->scope))
//        {
//            $form = $this->getList("form");
//        }
//        if (!$form)
//        {
//            $form =  $this->get('form');
//        }
//
//        $form_array = [];
//        $fields = $this->getFields();
//
//        if (is_array($form)) {
//
//            foreach ($form as $fname) {
//
//                $form_array[$fname] = $fields[$fname];
//            }
//
//            if (empty($prop)) {
//                return $form_array;
//            } else {
//
//                return $form_array[$prop];
//            }
//        }
//    }

//    public function getFields($prop='')
//    {
//        $form =  $this->get('fields');
//        if (empty($prop))
//        {
//            return $form;
//        } else{
//
//            if (!empty($form[$prop])) {
//                return $form[$prop];
//            }
//        }
//    }

//    public function getFilter($prop='')
//    {
////        $form = $this->get('filter.' . $this->scope);
////        if (empty($this->context) || $this->context == self::EMPTY_CONTEXT_LIST) {
////            $form = $this->get('filter');
////        } else
////        {
////            $form = $this->get('filter.'.$this->context);
////        }
//
//        //$form =  $this->get('filter');
//        $form = $this->get('list.' . $this->scope . '.filter');
//
//        if (empty($prop))
//        {
//            return $form;
//        } else{
//
//            return $form[$prop];
//        }
//    }

//    public function getColumn($col, $scope='fields')
//    {
//
//        if (!empty($this->config[$scope][$col]))
//        {
//            $conf =  $this->config[$scope][$col];
//            $conf['column_index'] = $col;
//        } else {
//            $conf =  $this->resolveColumnByRelationName($col, $scope);
//        }
//
//
//        return $conf;
//
//    }

//    function isColumnVisible($column)
//    {
//        if (is_null($this->list_prefs))
//        {
//            $this->list_prefs = false;
//            if ($this->app['auth']->check())
//            {
//                $user = $this->app['auth']->user();
//                if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
//                {
//                    $this->list_prefs = $user->crudPrefForModel(constant(get_class($user) . "::PREF_TYPE_COLUMN_LIST"), $this->model);
//                }
//            }
//        }
//        if (empty($this->list_prefs))
//        {
//            return true;
//        }
//        if (empty($this->list_prefs['columns']))
//        {
//            return true;
//        }
//        return in_array($column, $this->list_prefs['columns']);
//    }

//    protected   function resolveColumnByRelationName($col, $scope='fields')
//    {
//        foreach ($this->config[$scope] as $col_name => $desc) {
//            if (!empty($desc['relation_name']) &&  $desc['relation_name'] == $col)
//            {
//                $desc['column_index'] = $col_name;
//                return $desc;
//            }
//        }
//
//    }


//    function jsonSerialize()
//    {
//        $this->config['list'] = $this->getList();
//
//        if (!empty($this->config['list']['multiselect']))
//        {
//            array_unshift($this->config['list']['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
//        }
//
//        if (!empty($this->config['list']['buttons']['single_edit'])
//            || !empty($this->config['list']['buttons']['single_delete'])
//            || !empty($this->config['list']['list_actions'])
//
//        )
//        {
//            $this->config['list']['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
//        }
//
//        foreach($this->config['list']['columns'] as $k=>$col)
//        {
//
//            if (empty($col['title']))
//            {
//                $cdesc = $this->getColumn($col['data']);
//                if (!empty($cdesc['title'])) {
//                    $this->config['list']['columns'][$k]['title'] = $cdesc['title'];
//                }
//            }
//            if (!empty($col['hint']) && empty($col['hint']['index']))
//            {
//                $this->config['list']['columns'][$k]['hint']['index'] = $this->model->classViewName.'_'.$this->scope.'_'.$col['data'];
//            }
//            if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
//            {
//                unset($this->config['list']['columns'][$k]);
//            }
//        }
//        $this->config['filter'] = $this->getFilter();
//        if ($this->app['auth']->check())
//        {
//            $user = $this->app['auth']->user();
//            if ($user instanceof Contracts\PrefSubject)
//            {
//                $cols = $user->crudPrefFilterTableColumns($this->config['list']['columns'], $this);
//                foreach($this->config['list']['columns'] as $col)
//                {
//                    if (!empty($col['invisible']))
//                    {
//                        $cols[] = $col;
//                    }
//                }
//                $this->config['list']['columns'] = $cols;
//            }
//        }
//
//        if (!empty($this->config['list']['list_actions'])) {
////            $actions = [];
////            foreach ($this->config['list']['list_actions'] as $action) {
////                $actions[] = $action['title'].'|'.$action['command'].(isset($action['class'])?'|'.$action['class']:'');
////            }
////            $this->config['list_actions'] = implode(',',$actions);
//            $this->config['list_actions'] = json_encode($this->config['list']['list_actions']);
//        } else {
//            $this->config['list_actions'] = "";
//        }
//
//
//        $this->config['list_name'] = $this->getListName();
//        $this->config['scope'] = $this->scope;
//        return $this->config;
//    }

//    function isManyRelation($relation)
//    {
//        return in_array($relation, $this->manyRelations);
//    }

//    function getRelationNameByColumnName($colName)
//    {
//        $col = $this->getFields($colName);
//        if (!empty($col['relation_name']))
//        {
//            return $col['relation_name'];
//        }
//
//        return $colName;
//    }//


//    function getListDefaultFilter()
//    {
//        if (!empty($this->config['list'][$this->scope]['filter_default']))
//        {
//            return $this->config['list'][$this->scope]['filter_default'];
//        }
//
//        return [];
//    }

    function offsetExists($offset)
    {
        return true;
    }

    function offsetGet($offset)
    {
        $method = camel_case("get_" . $offset);
        if (method_exists($this, $method))
        {
            return $this->$method();
        }
        if (isset($this->config[$offset]))
        {
            return $this->config[$offset];
        }
    }

    function offsetSet($offset, $value)
    {

    }

    function offsetUnset($offset)
    {

    }



}