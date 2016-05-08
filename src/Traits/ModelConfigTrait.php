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
                        $this->fillable[] = $name;
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

        //FIXME
        //Back compability. Should be removed after projects update
//        $obj = $this;
//        $this->config['jsonSerialize()'] = ['dasdad' => 'dasdas'];
//        $this->config['jsonSerialize1'] = function() use ($obj){
//            $c = [];
//            $c['list'] = $obj->getListConfig();
//            if (!empty($c['list']['multiselect']))
//            {
//                array_unshift($c['list']['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
//            }
//            if (!empty($c['list']['buttons']['single_edit'])
//                || !empty($c['list']['buttons']['single_delete'])
//                || !empty($c['list']['list_actions'])
//
//            )
//            {
//                $c['list']['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
//            }
//
//            foreach($c['list']['columns'] as $k=>$col)
//            {
//                if (empty($col['title']))
//                {
//                    $cdesc = $obj->getColumn($col['data']);
//                    if (!empty($cdesc['title']))
//                    {
//                        $c['list']['columns'][$k]['title'] = $cdesc['title'];
//                    }
//                }
//                if (!empty($col['hint']) && empty($col['hint']['index']))
//                {
//                    $c['list']['columns'][$k]['hint']['index'] = $obj->classViewName.'_'.$obj->scope.'_'.$col['data'];
//                }
//                if (!empty($col['acl']) && !$obj->getApp()['skvn.cms']->checkAcl($col['acl'], 'r'))
//                {
//                    unset($c['list']['columns'][$k]);
//                }
//            }
//            $c['filter'] = $obj->getFilter();
//            if ($obj->getApp()['auth']->check())
//            {
//                $user = $obj->getApp()['auth']->user();
//                if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
//                {
//                    $cols = $user->crudPrefFilterTableColumns($c['list']['columns'], $obj);
//                    foreach($c['list']['columns'] as $col)
//                    {
//                        if (!empty($col['invisible']))
//                        {
//                            $cols[] = $col;
//                        }
//                    }
//                    $c['list']['columns'] = $cols;
//                }
//            }
//
//            if (!empty($c['list']['list_actions']))
//            {
//                $c['list_actions'] = json_encode($c['list']['list_actions']);
//            }
//            else
//            {
//                $c['list_actions'] = "";
//            }
//
//
//            $c['list_name'] = $obj->getListName();
//            $c['scope'] = $obj->scope;
//            return $c;
//        };
    }

    function objectifyConfig()
    {
        $conf = new \Skvn\Crud\CrudConfig($this);
        $c = $this->config;
        $c['list'] = $this->getListConfig();

        if (!empty($c['list']['multiselect']))
        {
            array_unshift($c['list']['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
        }

        if (!empty($c['list']['buttons']['single_edit'])
            || !empty($c['list']['buttons']['single_delete'])
            || !empty($c['list']['list_actions'])

        )
        {
            $c['list']['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
        }

        foreach($c['list']['columns'] as $k=>$col)
        {
            if (empty($col['title']))
            {
                $cdesc = $this->getColumn($col['data']);
                if (!empty($cdesc['title'])) {
                    $c['list']['columns'][$k]['title'] = $cdesc['title'];
                }
            }
            if (!empty($col['hint']) && empty($col['hint']['index']))
            {
                $c['list']['columns'][$k]['hint']['index'] = $this->classViewName.'_'.$this->scope.'_'.$col['data'];
            }
            if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
            {
                unset($c['list']['columns'][$k]);
            }
        }
        //$c['filter'] = $this->getFilter();
        if ($this->app['auth']->check())
        {
            $user = $this->app['auth']->user();
            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
            {
                $cols = $user->crudPrefFilterTableColumns($c['list']['columns'], $this);
                foreach($c['list']['columns'] as $col)
                {
                    if (!empty($col['invisible']))
                    {
                        $cols[] = $col;
                    }
                }
                $c['list']['columns'] = $cols;
            }
        }

        if (!empty($c['list']['list_actions'])) {
//            $actions = [];
//            foreach ($this->config['list']['list_actions'] as $action) {
//                $actions[] = $action['title'].'|'.$action['command'].(isset($action['class'])?'|'.$action['class']:'');
//            }
//            $this->config['list_actions'] = implode(',',$actions);
            $c['list_actions'] = json_encode($c['list']['list_actions']);
        } else {
            $c['list_actions'] = "";
        }


        $c['list_name'] = $this->getListName();
        $c['scope'] = $this->scope;
        $conf->setConfig($c);
        return $conf;

    }

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

    public function confParam($key, $default = null)
    {
        if (strpos($key,'.') === false)
        {
            return (!empty($this->config[$key]) ? $this->config[$key] : $default);
        }
        else
        {
            return $this->app['config']->get('crud.crud_'.$this->getTable().'.'.$key, $default);
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
        if (!empty($this->scope))
        {
            $form = $this->getListConfig("form");
        }
        if (!$form)
        {
            $form =  $this->confParam('form');
        }

        $form_array = [];
        $fields = $this->getFields();

        if (is_array($form))
        {
            foreach ($form as $fname)
            {
                $form_array[$fname] = $fields[$fname];
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
    }

    public function getListConfig($prop='')
    {

        if (strpos($prop,'.') === false)
        {
            $cols = $this->confParam('list.' . $this->scope);
            if (!empty($cols['multiselect']))
            {
                array_unshift($cols['columns'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
            }
            if (!empty($cols['buttons']['single_edit'])
                || !empty($cols['buttons']['single_delete'])
                || !empty($cols['list_actions'])

            )
            {
                $cols['columns'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
            }
            if (empty($prop))
            {
                return $cols;
            }
            else
            {
                if (isset($cols[$prop]))
                {
                    return $cols[$prop];
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
        $form = $this->confParam('list.' . $this->scope . '.filter');

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



}