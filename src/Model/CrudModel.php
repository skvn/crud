<?php namespace LaravelCrud\Model;

use \Illuminate\Database\Eloquent\Model;
//use \Illuminate\Database\Eloquent\SoftDeletingTrait;
use LaravelCrud\CrudConfig;
use LaravelCrud\Form\Form;
use LaravelCrud\Filter\FilterFactory;



class CrudModel extends Model {

    //use SoftDeletingTrait;

    public $config;
    protected $dirtyRelations = [];
    protected $classShortName;
    protected $filterObj;
    protected $form;
    protected $crudRelations;
    protected $codeColumn = 'id';

    protected $errors;
    protected static $rules = array();
    protected static $messages = array();
    protected $validator;
    protected $form_fields_collection;



    public function __construct(array $attributes = array(), \Validator $validator = null) {


        $this->classShortName = class_basename($this);
        if (empty($this->table))
        {
            $this->table = snake_case($this->classShortName);
        }

        $this->config = new CrudConfig($this);
        $this->fillable = $this->config->getFillable();
        $this->crudRelations = $this->config->getCrudRelations();

        if ($this->isTree())
        {
            $this->fillable[] = $this->columnTreePid;
            $this->fillable[] = $this->columnTreeOrder;
            $this->fillable[] = $this->columnTreePath ;
            $this->fillable[] = $this->columnTreeDepth;

        }

        parent::__construct($attributes);

        $this->validator = $validator ?: \App::make('validator');

    }

    


    public static function boot()
    {
        parent::boot();
        static::bootCrud();
    }

    public static function bootCrud()
    {
        static::saved(function($instance) {
            $instance->onAfterSave();
        });
        static::saving(function($instance)
        {
            return $instance->onBeforeSave();

        });

        static::creating(function($instance)
        {
            return $instance->onBeforeCreate();

        });

        static::created(function($instance)
        {
            return $instance->onAfterCreate();

        });

        static::deleting(function($instance)
        {
            return $instance->onBeforeDelete();

        });

        static::deleted(function($instance)
        {
            return $instance->onAfterDelete();

        });


    }

    protected  function onBeforeCreate()
    {

    }


    protected  function onAfterCreate()
    {

    }

    protected  function onBeforeDelete()
    {

    }


    protected  function onAfterDelete()
    {

    }

    protected  function onBeforeSave()
    {


        if ($this->validate()) {

            $dirty = $this->getDirty();

            if (count($dirty)) {

                $this->getForm($dirty, true);
                if (!empty($this->form->fields) && is_array($this->form->fields)) {
                    foreach ($dirty as $k => $v) {

                        if (isset($this->form->fields[$k])) {

                            $field = $this->form->fields[$k];
                            $val = $field->getValueForDb();
                            if ($val !== $v) {

                                $this->setAttribute($k, $val);
                            }
                        }
                    }
                }

            }

            return true;
        }

        return false;
    }

    protected  function onAfterSave()
    {
        return $this->saveRelations();
    }



    public function fillFromRequest(array $attributes)
    {


        foreach ($attributes as $k=>$v) {
            if (array_key_exists($k,$this->config->getProcessableRelations()))
            {
                $this->dirtyRelations[$k] = $v;
            }
        }

        foreach ($this->config->getProcessableRelations() as $k=>$v)
        {
            if (!array_key_exists($k,$attributes))
            {
               $this->dirtyRelations[$k] = null;
            }
        }


        return parent::fill($attributes);

    }
    function getListData($context=null, $viewType='data_tables')
    {

        $skip = (int) \Input::get('start',0);
        $take =  (int) \Input::get('length',0);
        $order = \Input::get('order');

        $coll = $this->getListCollection($context, $order);
        if (!$this->isTree()) {

            $coll = $this->applyCollectionFilters($coll, $context);
            $coll = $this->paginateListCollection($coll, $skip, $take);
        }
        
        return \App::make('CrudHelper')->prepareCollectionForView($coll, \Input::all(), $viewType);
    }


    function getListCollection($context=null, $order=null)
    {
        $scope = $this->purifyContext($context);

        if (!empty($context)) {

            $method = camel_case('get_' . $scope . '_list_collection');
        }


        //define if need eager join
        $listCols = $this->config->getList('columns');
        $sort = $this->config->getList('sort');
        $joins =[];
        foreach ($listCols as $listCol) {

            if ($relSpl = $this->resolveListRelation($listCol['data'])) {
                $joins[$relSpl[0]] = function ($query) {


                };

            }
        }




        if (!empty($context) && method_exists($this, $method))
        {

            return $this->$method($order, $joins);

        } else {


            if (!$this->isTree())
            {

                $basic = self::query();

            } else {
                //return \DB::table($this->table)->select(\DB::raw($this->table.'.*,  CONCAT(tree_path,id) as full_path'))->orderBy('full_path', 'asc');;
                return $this->getAllTree();

            }


            if (count($joins))
            {

                $basic = $basic->with($joins);
            }


            if (!empty($sort))
            {
                foreach ($sort as $o=> $v) {
                    $basic->orderBy($o, $v);
                }


            }

            return $basic;


        }
    }

    protected  function paginateListCollection($coll, $skip, $take)
    {
        $coll->cnt = $coll->count();
        if ($take>0)
        {
            $coll = $coll->skip($skip)->take($take);

        }

        return $coll;
    }

    protected  function applyCollectionFilters($coll, $context)
    {
        $context = $this->purifyContext($context);
        $this->initFilter($context);

        $scope = $this->purifyContext($context);


        if (!empty($context)) {

            $methodCond = camel_case('append_' . $scope . '_conditions');
        }

        $conditions = $this->filterObj->getConditions();
        if (method_exists($this,$methodCond))
        {
            $conditions= $this->$methodCond($conditions);
        } else {
            $conditions = $this->appendConditions($conditions);
        }


        if (is_array($conditions)) {
            $coll = $this->applyConditions($coll, $conditions);
            $coll->cnt = $coll->count();
        }


        return $coll;

    }//

    public  function appendConditions($conditions)
    {

        return $conditions;
    }

    public function applyConditions($coll, $conditions)
    {
        foreach ($conditions as $cond) {

            if (empty($cond['join'])) {

                if (!empty($cond['cond'])) {
                    $coll = $this->applyFilterWhere($coll, $cond['cond']);
                }
            } else {
                //use joins
                $coll-> whereHas($cond['join'], function($query) use ($cond) {
                    $query = $this->applyFilterWhere($query, $cond['cond']);
                });
            }

        }

        return $coll;
    }//


    protected function applyFilterWhere($coll, $cond)
    {
        list($col, $act, $val) = $cond;
        switch (strtolower($act))
        {
            case 'in':
                $coll->whereIn($col, $val);
                break;

            case 'between':
                $coll->whereBetween($col, $val);
                break;

            default:
                $coll->where($col, $act, $val);
                break;
        }


        return $coll;
    }

    public function setCreatedAtAttribute($value)
    {

        if (is_object($value))
        {
            $value = $value->timestamp;
        } else
        {
            $value = strtotime($value);
        }
        $this->attributes['created_at'] = $value;
    }
    public function setUpdatedAtAttribute($value)
    {

        if (is_object($value))
        {
            $value = $value->timestamp;
        } else
        {
            $value = strtotime($value);
        }

        $this->attributes['updated_at'] = $value;
    }



//
//    public function scopeSelect($query, $title = 'Select') {
//        $selectVals[''] = $title;
//        $selectVals += $this->lists($this->config->get('title_field'), 'id');
//        return $selectVals;
//    }

    public function saveRelations()
    {


        $formConf = $this->config->getFields();

        if ($this->dirtyRelations  && is_array($this->dirtyRelations )) {
            foreach ($this->dirtyRelations as $k => $v) {

                switch ($this->config->getCrudRelations()[$k]) {
                    case 'hasMany':
                        $class = '\App\Model\\' . $formConf[$k]['model'];
                        if (is_array($v)) {
                            $oldIds = $this->$k()->lists('id');
                            foreach ($v as $id) {

                                $obj = $class::find($id);
                                $this->$k()->save($obj);
                            }
                            $toUnlink = array_diff($oldIds, $v);


                        } else {
                            $toUnlink = $this->$k()->lists('id');

                        }

                        if ($toUnlink && is_array($toUnlink)) {
                            foreach ($toUnlink as $id) {
                                if (!empty($formConf[$k]['ref_column'])) {
                                    $col = $formConf[$k]['ref_column'];
                                } else {

                                    $col = snake_case($this->classShortName . 'Id');
                                }
                                $obj = $class::find($id);
                                $obj->$col = null;
                                $obj->save();
                            }
                        }

                        break;
                    case 'belongsToMany':


                        if (is_array($v)) {

                            $this->$k()->sync($v);
                        } else {
                            $this->$k()->sync([]);
                        }
                        //$this->load($k);

                        break;
                }

            }
        }
        $this->dirtyRelations = null;
    }//


    public function __call($method, $parameters)
    {


        if (array_key_exists($method, $this->config->getCrudRelations()))
        {
            $relType =  $this->config->getCrudRelations()[$method];
            $relAttributes = $this->config->getColumn($method);

            return $this->createCrudRelation($relType, $relAttributes, $method);

        }
        return parent::__call($method, $parameters);
    }

    private function createCrudRelation($relType, $relAttributes, $method)
    {


        switch ($relType)
        {
            case 'belongsTo':
                return $this->$relType('\App\Model\\'.$relAttributes['model'],$relAttributes['column_index'], null, $method);
            break;

            case 'belongsToMany':
                return $this->$relType('\App\Model\\'.$relAttributes['model'],null, null, null, $method);
                break;

            default:
                return $this->$relType('\App\Model\\'.$relAttributes['model']);
                break;


        }

    }
    public function getAttribute($key)
    {


        if (array_key_exists($key, $this->config->getCrudRelations()))
        {

            if ( ! array_key_exists($key, $this->relations)) {
                $camelKey = camel_case($key);

                return $this->getRelationshipFromMethod($key, $camelKey);
            }
        }
        $method = camel_case("attr_" . $key);
        if (method_exists($this, $method))
        {
            return $this->$method();
        }


        return parent::getAttribute($key);
    }

    public function getRelationIds($relation)
    {

        return $this->$relation->lists('id');
    }

//    public function getInputValue($column, $value=null)
//    {
//        if ($value)
//        {
//            return $value;
//        }
//
//        return $this->getAttribute($column);
//    }

    public function initFilter($listOrContext=CrudConfig::EMPTY_CONTEXT_LIST)
    {

        $listOrContext = $this->purifyContext($listOrContext);
        if ($listOrContext != CrudConfig::EMPTY_CONTEXT_LIST) {
            $this->config->setContext($listOrContext);
        }

        $filter =  FilterFactory::create([$this->classShortName,$listOrContext]);
        $this->setFilter($filter);
    }

    public function setFilter(\LaravelCrud\Filter\Filter $filterObj)
    {
        $this->filterObj = $filterObj;
        $this->filterObj->setModel($this);
    }
    public  function getFilter()
    {
        if (!$this->filterObj)
        {
            throw new \InvalidArgumentException("Filter object is not set");
        }
        return $this->filterObj;
    }

    public function getFilterColumns()
    {
        return $this->filterObj->filters;
    }

    public function fillFilter($context,$input)
    {
        $this->initFilter($context);

        return $this->filterObj->fill($input, true);
    }

    public function getForm($fillData=null, $forceNew=false)
    {
        if ($forceNew ||  !$this->form)
        {

            $this->form = new Form($this,$this->config->getForm(), $fillData);
        }

        return $this->form;
    }

    public function getFieldsObjects($fillData=null)
    {
        if (!$this->form_fields_collection) {
            $form = new Form($this, $this->config->getFields(), $fillData);
            $this->form_fields_collection = $form->fields;
        }

        return $this->form_fields_collection;

    }

    protected  function purifyContext($context)
    {

        if (strpos($context, ':') !== false)
        {

            $context = str_replace($this->classShortName.':','',$context);

        }
        return $context;
    }

    function isTree()
    {
        return $this->config->get('tree');
    }

    function getTitle()
    {
        $title = ($this->config->get('title_field')?$this->config->get('title_field'):'title');
        return $this->getAttribute($title);
    }

    /**
     * returns value by a config style declaration
     * @param $col
     *
     */
    function getDescribedColumnValue($col)
    {


        if ($relSpl = $this->resolveListRelation($col))
        {

            $rel = $relSpl[0];
            $attr = $relSpl[1];

            return $this->$rel->$attr;
            

        } else {
            if ($this->__isset($col)) {
                return $this->$col;
            } else {
                $meth = camel_case('get_' . $col);
                if (method_exists($this, $meth)) {
                    return $this->$meth();
                }
            }
        }
    }

    function checkAcl($access = "")
    //function checkAcl()
    {

        $helper = \App::make('CmsHelper');
        if (!$this->config->get('acl'))
        {
            return true;
        }
        return $helper->checkAcl($this->config->get('acl'), $access);
        //return $helper->checkAcl($this->config->get('acl'));
    }

    function getInternalCodeAttribute()
    {
        return $this->attributes[$this->codeColumn];
    }

    private function resolveListRelation($alias)
    {
        if (strpos($alias,'::') !== false)
        {
            return explode('::',$alias);
        }
        return false;
    }



    public function validate()
    {

        $v = $this->validator->make($this->attributes, static::$rules, static::$messages);
        if ($v->passes())
        {
            return true;
        }
        $this->setErrors($v->messages());
        return false;
    }


    protected function setErrors($errors)
    {
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }


    public function hasErrors()
    {
        return ! empty($this->errors);
    }


    function attrModelName()
    {
        return snake_case($this->classShortName);
    }


}
