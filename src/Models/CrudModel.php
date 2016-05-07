<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\CrudConfig;
use Skvn\Crud\Exceptions\Exception as CrudException;
use Skvn\Crud\Form\FieldFactory;
use Skvn\Crud\Form\Form;
use Skvn\Crud\Traits\ModelListTrait;
use Illuminate\Support\Collection ;



class CrudModel extends Model
{
    use ModelListTrait;

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
            if (!$this->config->exists('table'))
            {
                $this->table = $this->classViewName;
            }
            else
            {
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

            //process dirty attributes
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

    /**
     *  Save relations
     */

    public function saveRelations()
    {


        $formConf = $this->config->getFields();

        if ($this->dirtyRelations  && is_array($this->dirtyRelations )) {

            $form = $this->getForm($this->dirtyRelations, true);

            foreach ($this->dirtyRelations as $k => $v) {

                if (!empty($form->fields[$k]))
                {
                    $v = $form->fields[$k]->getValueForDb();
                }

                switch ($this->config->getCrudRelations()[$k]) {

                    case 'hasOne':
                        $class = $this->app['skvn.crud']->getModelClass($formConf[$k]['model']);
                        $relObj = $class::find($v);
                        $relObj->setAttribute($formConf[$k]['ref_column'], $this->id);
                        $relObj->save();
                    break;

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
        return $this->config->get('tree') && !$this->config->get('tree')['use_list'];
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
                $form_config = $this->config->getFields($col);
                if ($form_config && !empty($form_config['type']))
                {
                    $form_config['name'] = $col;
                    $field = FieldFactory::create($this->getForm(), $form_config);
                    return $field->getValueForList();
                }
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

    function getAutocompleteList($query)
    {
        if (!$this->config->get('title_field'))
        {
            throw new CrudException('Unable to init AutocompleteList: title_field is not configured');
        }

        if (!empty($query)) {
            return self::where($this->config->get('title_field'), 'LIKE', $query . '%')
                ->pluck($this->config->get('title_field'));
        }

        return [];
    }

}
