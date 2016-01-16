<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\CrudConfig;
use Skvn\Crud\Form\Form;
use Skvn\Crud\Filter\FilterFactory;
use Illuminate\Support\Collection ;
use Illuminate\Foundation\Application as LaravelApplication;



class CrudModel extends Model {

    //use SoftDeletingTrait;

    protected $app;

    public $config;
    protected $dirtyRelations = [];
    public $classShortName;
    public $classViewName;
    protected $filterObj;
    protected $form;
    protected $crudRelations;
    protected $codeColumn = 'id';

    protected $errors;
    protected static $rules = array();
    protected static $messages = array();
    protected $validator;
    protected $form_fields_collection;

    /* Flag for tracking created_by  and updated_by */
    protected $track_authors = false;



    public function __construct(array $attributes = array(), $validator = null)
    {
        $this->app = app();

        $this->classShortName = class_basename($this);
        $this->classViewName = snake_case($this->classShortName);
        $this->config = new CrudConfig($this);

        if (empty($this->table))
        {
            if (!$this->config->exists('table')) {
                $this->table = snake_case($this->classShortName);
            } else {
                $this->table = $this->config->get('table');
            }
        }

        if ($this->config->exists('timestamps'))
        {
            $this->timestamps = $this->config->get('timestamps');
        }

        if ($this->config->exists('authors'))
        {
            $this->track_authors = $this->config->get('authors');
        }

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

        $this->validator = $validator ?: $this->app['validator'];

    }

    public static function boot()
    {
        parent::boot();
        static::bootCrud();
    }

    public static function bootCrud()
    {
        static::saved(function($instance) {

            return $instance->onAfterSave();
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
        if ($this->track_authors && $this->app['auth']->check())
        {
            $this->created_by = $this->app['auth']->user()->id;
        }

        return true;
    }


    protected  function onAfterCreate()
    {
        return true;
    }

    protected  function onBeforeDelete()
    {
        return true;
    }


    protected  function onAfterDelete()
    {
        return true;
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

            if ($this->track_authors && $this->app['auth']->check())
            {
                $this->updated_by = $this->app['auth']->user()->id;
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

    function getListData($scope=null, $viewType='data_tables')
    {
        $skip = (int) $this->app['request']->get('start',0);
        $take =  (int) $this->app['request']->get('length',0);
        $order = $this->app['request']->get('order');

        if (!empty($scope))
        {
            $this->config->setScope($scope);

        }
        $config_cols = $this->config->getList('columns');
        $coll = $this->getListCollection($scope, $order);
        if (!$this->isTree()) {

            $coll = $this->applyCollectionFilters($coll, $scope);
            $coll = $this->paginateListCollection($coll, $skip, $take);
        }
        
        return $this->app['skvn.crud']->prepareCollectionForView($coll, $this->app['request']->all(), $viewType, $config_cols);
    }


    function getListCollection($scope=null, $order=null)
    {
        //$scope = $this->purifyContext($context);

        if (!empty($scope))
        {
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

        if (!empty($scope) && method_exists($this, $method))
        {
            return $this->$method($order, $joins);
        }
        else
        {


//            if (!$this->isTree())
//            {

                $basic = self::query();
//
//            } else {
//                //return \DB::table($this->table)->select(\DB::raw($this->table.'.*,  CONCAT(tree_path,id) as full_path'))->orderBy('full_path', 'asc');;
//                return $this->getAllTree();
//
//            }


            if (count($joins))
            {

                $basic = $basic->with($joins);
            }


            if ($this->isTree())
            {
                $basic->orderBy($this->getColumnTreePath() , 'asc');
                $basic->orderBy($this->getColumnTreeOrder(), 'asc');

            } else {

                if (!empty($sort)) {
                    foreach ($sort as $o => $v) {
                        $basic->orderBy($o, $v);
                    }

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

    public  function applyCollectionFilters($coll, $scope)
    {
        //$context = $this->purifyContext($context);
        $this->initFilter($scope);

        //$scope = $this->purifyContext($context);


        if (!empty($scope)) {

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
        $conditions = $this->preApplyConditions($coll,$conditions);

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

    public  function preApplyConditions($coll,$conditions)
    {
        return $conditions;
    }

    protected function applyFilterWhere($coll, $cond)
    {
        if (is_string($cond))
        {
            $coll->whereRaw($cond);
        }
        else if (is_array($cond[0]))
        {
            //OR in AND
            $or_where = function ($query) use ($cond) {

                foreach ($cond as $i=>$one_cond)
                {
                    list($col, $act, $val) = $one_cond;
                    if ($i ==0)
                    {
                        $query = $this->applyFilterWhere($query,$one_cond);
                    } else {
                        $query = $this->applyFilterOrWhere($query,$one_cond);
                    }

                }
            };
            
            $coll->where($or_where);

        } else {

            //simple and
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
        }

        return $coll;
    }//


    protected function applyFilterOrWhere($coll, $cond)
    {
        list($col, $act, $val) = $cond;
        switch (strtolower($act))
        {
            case 'in':
                $coll->orWhereIn($col, $val);
                break;

            case 'between':
                $coll->orWhereBetween($col, $val);
                break;

            default:
                $coll->orWhere($col, $act, $val);
                break;
        }

        return $coll;
    }

    public function setCreatedAtAttribute($value)
    {

        $type = $this->config->get('timestamps_type');
        if (!$type || $type == 'int') {
            if (is_object($value)) {
                $value = $value->timestamp;
            } else {
                $value = strtotime($value);
            }

        }

        $this->attributes['created_at'] = $value;
    }

    public function setUpdatedAtAttribute($value)
    {

        $type = $this->config->get('timestamps_type');
        if (!$type || $type == 'int') {
            if (is_object($value)) {
                $value = $value->timestamp;
            } else {
                $value = strtotime($value);
            }
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
                        $class = $this->app['skvn.crud']->getModelClass($formConf[$k]['model']);
                        //$class = '\App\Model\\' . $formConf[$k]['model'];
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
            case \Skvn\Crud\CrudConfig::RELATION_BELONGS_TO:
                //return $this->$relType('\App\Model\\'.$relAttributes['model'],$relAttributes['column_index'], null, $method);
                return $this->$relType($this->app['skvn.crud']->getModelClass($relAttributes['model']), $relAttributes['column_index'], null, $method);
            break;

            case \Skvn\Crud\CrudConfig::RELATION_HAS_ONE:
                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
                return $this->$relType($this->app['skvn.crud']->getModelClass($relAttributes['model']),  $ref_col);
                break;

            case \Skvn\Crud\CrudConfig::RELATION_BELONGS_TO_MANY:
                //return $this->$relType('\App\Model\\'.$relAttributes['model'],null, null, null, $method);
                $pivot_table = (!empty($relAttributes['pivot_table'])?$relAttributes['pivot_table']:null);
                $pivot_self_column = (!empty($relAttributes['pivot_self_key'])?$relAttributes['pivot_self_key']:null);
                $pivot_foreign_column = (!empty($relAttributes['pivot_foreign_key'])?$relAttributes['pivot_foreign_key']:null);
                return $this->$relType($this->app['skvn.crud']->getModelClass($relAttributes['model']), $pivot_table, $pivot_self_column, $pivot_foreign_column, $method);
                break;

            case \Skvn\Crud\CrudConfig::RELATION_HAS_MANY:
                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
                return $this->$relType($this->app['skvn.crud']->getModelClass($relAttributes['model']), $ref_col );
                break;

            default:
                return $this->$relType($this->app['skvn.crud']->getModelClass($relAttributes['model']));
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
        $data = $this->$relation->lists('id');
        if (is_object($data) && ($data instanceof Collection))
        {
            $data = $data->all();
        }

        return $data;
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

    public function initFilter(/*$scope = CrudConfig :: DEFAULT_SCOPE*/)
    {

        //$listOrContext = $this->purifyContext($listOrContext);
//        if ($scope != CrudConfig :: T_LIST) {
            //$this->config->setScope($scope);
//        }

        $filter =  FilterFactory::create($this, $this->config->getScope());
        $this->setFilter($filter);
    }

    public function setFilter(\Skvn\Crud\Filter\Filter $filterObj)
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

    public function fillFilter($scope, $input)
    {
        $this->initFilter($scope);

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

//    protected  function purifyContext($context)
//    {
//
//        if (strpos($context, ':') !== false)
//        {
//
//            $context = str_replace($this->classShortName.':','',$context);
//
//        }
//        return $context;
//    }

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
    {

        if (!$this->config->get('acl'))
        {
            return true;
        }
        return $this->app['skvn.cms']->checkAcl($this->config->get('acl'), $access);
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
