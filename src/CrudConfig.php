<?php namespace Skvn\Crud;

use JsonSerializable, ArrayAccess;

class CrudConfig implements JsonSerializable, ArrayAccess {


    const FIELD_SELECT = 'select';
    const FIELD_TEXT = 'text';
    const FIELD_FILE = 'file';
    const FIELD_CHECKBOX = 'checkbox';
    const FIELD_MULTI_FILE = 'multi_file';
    const FIELD_TEXTAREA = 'textarea';
    const FIELD_DATE = 'date';
    const FIELD_RANGE = 'range';
    const FIELD_DATE_RANGE = 'date_range';
    const FIELD_NUMBER = 'number';

    const RELATION_BELONGS_TO_MANY = 'belongsToMany';
    const RELATION_BELONGS_TO = 'belongsTo';
    const RELATION_HAS_MANY = 'hasMany';
    const RELATION_HAS_ONE = 'hasOne';

    const DEFAULT_SCOPE = 'default';





    protected $config;
    protected $crudRelations = [];
    protected $processableRelations = [];
    protected $fillable = [];
    protected $manyRelations = array('hasMany','belongsToMany', 'morphToMany', 'morphedByMany');
    protected $scope = "default";
    protected $model;
    protected $list_prefs = null;
    protected $app;


    public function __construct($model)
    {
        $this->app = app();

        $this->model = $model;

        $this->config =   $this->app['config']->get('crud.crud_'.$model->getTable());
        $this->config['class_name'] = snake_case(class_basename($model));


        if (!empty($this->config['fields']))
        {
            if (!isset($this->config['form']))
            {
                $this->config['form'] = array_keys($this->config['fields']);
            }
            foreach ($this->config['fields'] as $name=>  $col)
            {
//                if (!empty($col['hidden']))
//                {
//                    continue;
//                }

                if (!empty($col['hint_default']) && !empty($col['hint']) &&  $col['hint'] === 'auto')
                {
                    $col['hint'] = $this->model->classShortName.'_fields_'.$name;
                }
                //fill relations
                if (isset($col['relation']))
                {
                    $rel_name = $this->getRelationNameByColumnName($name);
                    $this->crudRelations[$rel_name] = $col['relation'];
                }

                //if field in form - make it fillable or processable as relation
                if (in_array($name,$this->config['form']) || !empty($col['fillable'])) {

                    if (isset($col['relation']) &&
                        ($col['relation'] == 'belongsToMany' || $col['relation'] == 'hasMany')

                    ) {


                        $this->config['fields'][$name]['multiple'] = 1;
                        //Add multi file to fillable since it is handled by fill not by post save relations
                        if ($col['type'] != 'multi_file') {
                            $this->processableRelations[$name] = $col['relation'];
                        } else {
                            $this->fillable[] = $name;
                        }
                        continue;

                    } else {
                        $this->fillable[] = $name;
                    }
                }


            }
        }


    }

    public function get($key)
    {
        if (strpos($key,'.') === false) {
            return (!empty($this->config[$key]) ? $this->config[$key] : false);
        } else
        {
            
            return $this->app['config']->get('crud.crud_'.$this->model->getTable().'.'.$key);

        }
    }

    public function exists($key)
    {

         return isset($this->config[$key]);

    }

    public function getCrudRelations()
    {
        return $this->crudRelations;
    }

    public function getProcessableRelations()
    {
        return $this->processableRelations;
    }

    public function getFillable()
    {
        return $this->fillable;
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
    }

    function getScope()
    {
        return $this->scope;
    }

    public  function getListName()
    {
        return $this->scope ? $this->scope : self :: DEFAULT_SCOPE;
    }

    public function getList($prop='')
    {

        if (strpos($prop,'.') === false) {

            $cols = $this->get('list.' . $this->scope);

            if (empty($prop))
            {
                return $cols;
            } else{

                if (isset($cols[$prop])) {
                    return $cols[$prop];
                }
            }

        } else
        {

            return $this->app['config']->get('crud.crud_'.$this->model->getTable().'.list.'.$this->scope.'.'.$prop);

        }

    }

    public function getForm($prop='')
    {
        $form =  $this->get('form');

        $form_array = [];
        $fields = $this->getFields();

        if (is_array($form)) {

            foreach ($form as $fname) {

                $form_array[$fname] = $fields[$fname];
            }

            if (empty($prop)) {
                return $form_array;
            } else {

                return $form_array[$prop];
            }
        }
    }

    public function getFields($prop='')
    {
        $form =  $this->get('fields');
        if (empty($prop))
        {
            return $form;
        } else{

            return $form[$prop];
        }
    }

    public function getFilter($prop='')
    {
//        $form = $this->get('filter.' . $this->scope);
//        if (empty($this->context) || $this->context == self::EMPTY_CONTEXT_LIST) {
//            $form = $this->get('filter');
//        } else
//        {
//            $form = $this->get('filter.'.$this->context);
//        }

        //$form =  $this->get('filter');
        $form = $this->get('list.' . $this->scope . '.filter');

        if (empty($prop))
        {
            return $form;
        } else{

            return $form[$prop];
        }
    }

    public function getColumn($col, $scope='fields')
    {

        if (!empty($this->config[$scope][$col]))
        {
            $conf =  $this->config[$scope][$col];
            $conf['column_index'] = $col;
        } else {
            $conf =  $this->resolveColumnByRelationName($col, $scope);
        }


        return $conf;

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
                    $this->list_prefs = $user->crudPrefForModel(constant(get_class($user) . "::PREF_TYPE_COLUMN_LIST"), $this->model);
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

    protected   function resolveColumnByRelationName($col, $scope='fields')
    {
        foreach ($this->config[$scope] as $col_name => $desc) {
            if (!empty($desc['relation_name']) &&  $desc['relation_name'] == $col)
            {
                $desc['column_index'] = $col_name;
                return $desc;
            }
        }

    }
    public function __toJson()
    {

    }


    function jsonSerialize()
    {
        $this->config['list'] = $this->getList();

        if (!empty($this->config['list']['multiselect']))
        {
            array_unshift($this->config['list']['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
        }

        if (!empty($this->config['list']['buttons']['single_edit']) || !empty(!empty($this->config['list']['buttons']['single_delete'])))
        {
            $this->config['list']['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
        }

        foreach($this->config['list']['columns'] as $k=>$col)
        {

            if (empty($col['title']))
            {
                $cdesc = $this->getColumn($col['data']);
                if (!empty($cdesc['title'])) {
                    $this->config['list']['columns'][$k]['title'] = $cdesc['title'];
                }
            }
            if (!empty($col['hint']) && empty($col['hint']['index']))
            {
                $this->config['list']['columns'][$k]['hint']['index'] = $this->model->classViewName.'_'.$this->scope.'_'.$col['data'];
            }
            if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
            {
                unset($this->config['list']['columns'][$k]);
            }
        }
        $this->config['filter'] = $this->getFilter();
        if ($this->app['auth']->check())
        {
            $user = $this->app['auth']->user();
            if ($user instanceof Contracts\PrefSubject)
            {
                $cols = $user->crudPrefFilterTableColumns($this->config['list']['columns'], $this);
                foreach($this->config['list']['columns'] as $col)
                {
                    if (!empty($col['invisible']))
                    {
                        $cols[] = $col;
                    }
                }
                $this->config['list']['columns'] = $cols;
            }
        }

        $this->config['list_name'] = $this->getListName();
        $this->config['scope'] = $this->scope;
        return $this->config;
    }

    function isManyRelation($relation)
    {
        return in_array($relation, $this->manyRelations);
    }

    function getRelationNameByColumnName($colName)
    {
        $col = $this->getFields($colName);
        if (!empty($col['relation_name']))
        {
            return $col['relation_name'];
        }

        return $colName;
    }//


    function getListDefaultFilter()
    {
        if (!empty($this->config['list'][$this->scope]['filter_default']))
        {
            return $this->config['list'][$this->scope]['filter_default'];
        }

        return [];
    }

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



    static  function getAvailableRelationFieldTypes()
    {
        return [
            self::FIELD_SELECT => 'Select',

        ];
    }
}