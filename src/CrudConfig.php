<?php

namespace LaravelCrud;

use JsonSerializable;

class CrudConfig implements JsonSerializable {

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

    const EMPTY_CONTEXT_LIST = 'def';



    protected $config;
    protected $crudRelations = [];
    protected $processableRelations = [];
    protected $fillable = [];
    protected $manyRelations = array('hasMany','belongsToMany', 'morphToMany', 'morphedByMany');
    protected $context;


    public function __construct($table_name)
    {


        $this->config =   \Config::get('crud_'.$table_name);

        $this->config['class_name'] = $table_name;


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
            return \Config::get('crud_'.$this->config['class_name'].'.'.$key);

        }
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

    public function setContext($context)
    {
        if ($context == 'null')
        {
            return;
        }
        $this->context = $context;
    }

    public  function getListName()
    {
        if (!empty($this->context))
        {
            return $this->context;
        } else {
            return self::EMPTY_CONTEXT_LIST;
        }
    }

    public function getList($prop='')
    {

        if (empty($this->context) || $this->context == self::EMPTY_CONTEXT_LIST) {
            $cols = $this->get('list');
        } else
        {
            $cols = $this->get('list.'.$this->context);
        }


        if (empty($prop))
        {
            return $cols;
        } else{

            if (isset($cols[$prop])) {
                return $cols[$prop];
            }
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
        if (empty($this->context) || $this->context == self::EMPTY_CONTEXT_LIST) {
            $form = $this->get('filter');
        } else
        {
            $form = $this->get('filter.'.$this->context);
        }

        //$form =  $this->get('filter');
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
            return $this->config[$scope][$col];
        } else {
            return $this->resolveColumnByRelationName($col, $scope);
        }

    }

    protected   function resolveColumnByRelationName($col, $scope='fields')
    {
        foreach ($this->config[$scope] as $col_name => $desc) {
            if (!empty($desc['relation_name']) &&  $desc['relation_name'] == $col)
            {
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
        foreach($this->config['list']['columns'] as $k=>$col)
        {
            if (empty($col['title']))
            {
                $cdesc = $this->getColumn($col['data']);
                if (!empty($cdesc['title'])) {
                    $this->config['list']['columns'][$k]['title'] = $cdesc['title'];
                }
            }
        }

        $this->config['list_name'] = $this->getListName();
        $this->config['context'] = $this->context;
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


        if (!empty($this->config['list'][$this->context]['filter']))
        {
            return $this->config['list'][$this->context]['filter'];
        }

        return [];
    }
}