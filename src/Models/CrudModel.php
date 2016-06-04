<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\Traits\ModelInjectTrait;
use Skvn\Crud\Traits\ModelConfigTrait;
use Skvn\Crud\Traits\ModelRelationTrait;
use Skvn\Crud\Traits\ModelFilterTrait;
use Skvn\Crud\Traits\ModelFormTrait;

use Illuminate\Container\Container;

abstract class CrudModel extends Model
{
    use ModelInjectTrait;
    use ModelConfigTrait;
    use ModelRelationTrait;
    use ModelFilterTrait;
    use ModelFormTrait;

    //use SoftDeletingTrait;

    const RELATION_BELONGS_TO_MANY = 'belongsToMany';
    const RELATION_BELONGS_TO = 'belongsTo';
    const RELATION_HAS_MANY = 'hasMany';
    const RELATION_HAS_ONE = 'hasOne';

    const DEFAULT_SCOPE = 'default';
    const CREATED_BY = "created_by";
    const UPDATED_BY = "updated_by";


    protected $app;
    protected $codeColumn = 'id';

    protected $errors;
    protected static $rules = array();
    protected static $messages = array();
    protected $validator;


    public function __construct(array $attributes = array(), $validator = null)
    {
        $this->app = Container :: getInstance();
        $this->bootIfNotBooted();
        $this->preconstruct();
        //$this->initConfig();
        parent::__construct($attributes);
        $this->postconstruct();

        $this->validator = $validator ?: $this->app['validator'];
    }

    static function resolveClass($model)
    {
        $app = Container :: getInstance();
        return $app['config']['crud_common.model_namespace'] . '\\' . studly_case($model);
    }

    static function createInstance($model, $scope = self :: DEFAULT_SCOPE, $id = null)
    {
        $class = static :: resolveClass($model);
        if (!empty($id))
        {
            $obj = $class::firstOrNew(['id'=>(int)$id]);
        }
        else
        {
            $obj = new $class();
//            $app = Container :: getInstance();
//            $obj = $app->make($class);
        }
        $obj->setScope($scope);
        return $obj;
    }

    protected  function onBeforeCreate()
    {
        if ($this->track_authors && $this->app['auth']->check())
        {
            $this->{static :: CREATED_BY} = $this->app['auth']->user()->id;
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
        if ($this->validate())
        {
            $dirty = $this->getDirty();

            //process dirty attributes
            if (count($dirty))
            {
                $this->getForm(['fillData'=>$dirty,'forceNew' => true]);
                if (!empty($this->form->fields) && is_array($this->form->fields))
                {
                    foreach ($dirty as $k => $v)
                    {
                        if (isset($this->form->fields[$k]))
                        {
                            $field = $this->form->fields[$k];
                            $val = $field->getValueForDb();
                            if ($val !== $v)
                            {
                                $this->setAttribute($k, $val);
                            }
                        }
                    }
                }
            }
            if ($this->track_authors && $this->app['auth']->check())
            {
                $this->{static :: UPDATED_BY} = $this->app['auth']->user()->id;
            }
            return true;
        }
        return false;
    }

    protected  function onAfterSave()
    {
        return $this->saveRelations();
    }

    function getApp()
    {
        return $this->app;
    }

    public function fillFromRequest(array $attributes)
    {
        foreach ($attributes as $k=>$v)
        {
            if (array_key_exists($k, $this->processableRelations))
            {
                $this->dirtyRelations[$k] = $v;
            }
        }

        foreach ($this->processableRelations as $k=>$v)
        {
            if (!array_key_exists($k,$attributes))
            {
               $this->dirtyRelations[$k] = null;
            }
        }

        foreach ($this->config['fields'] as $col_idx => $col)
        {
            if (!empty($col['fields']))
            {
                foreach ($col['fields'] as $f)
                {
                    if (!empty($attributes[$f]))
                    {
                        $attributes[$f] = $this->getForm()->fields[$col_idx]->prepareValueForDb($attributes[$f]);
                    }
                }
            }
        }

        return parent::fill($attributes);
    }

    public function setCreatedAtAttribute($value)
    {
        $type = $this->confParam('timestamps_type');
        if (!$type || $type == 'int')
        {
            if (is_object($value))
            {
                $value = $value->timestamp;
            }
            else
            {
                $value = strtotime($value);
            }
        }
        $this->attributes['created_at'] = $value;
    }

    public function setUpdatedAtAttribute($value)
    {
        $type = $this->confParam('timestamps_type');
        if (!$type || $type == 'int')
        {
            if (is_object($value))
            {
                $value = $value->timestamp;
            }
            else
            {
                $value = strtotime($value);
            }
        }
        $this->attributes['updated_at'] = $value;
    }

    public function __call($method, $parameters)
    {
        //var_dump("__call");
        //if (array_key_exists($method, $this->crudRelations))
        //if (array_key_exists($method, $this->config['fields']))
        if ($col = $this->getCrudRelation($method))
        {
            //$col = $this->config['fields'][$method];
            //if (!empty($col['relation']))
            //{
//                $relType =  $this->crudRelations[$method];
//                $relAttributes = $this->getColumn($method);
//                return $this->createCrudRelation($relType, $relAttributes, $method);
                return $this->createCrudRelation($col['relation'], /*$this->getColumn($method)*/$col, $method);
            //}

        }
        return parent::__call($method, $parameters);
    }

    function __isset($key)
    {
        $col = $this->config['fields'][$key] ?? [];
        if (!empty($col['fields']))
        {
            foreach ($this->config['fields'][$key]['fields'] as $f)
            {
                if (parent :: __isset($f))
                {
                    return true;
                }
            }
        }
        if (!empty($col['field']) && $col['field'] !== $key)
        {
            return parent :: __isset($col['field']);
        }
        return parent :: __isset($key);
    }

    public function getAttribute($key)
    {
        //var_dump("getAttribute");
        if ($this->getCrudRelation($key))
        //if (array_key_exists($key, $this->crudRelations))
        {
            if ( ! array_key_exists($key, $this->relations))
            {
                $camelKey = camel_case($key);
                return $this->getRelationshipFromMethod($key, $camelKey);
            }
        }

        return parent::getAttribute($key);
    }

    function setAttribute($key, $value)
    {
        if ($this->callSetters($key, $value) === true)
        {
            return;
        }
        return parent :: setAttribute($key, $value);
    }

    function getTitle()
    {
        $param = $this->confParam('title_field', 'title');
        return $this->getAttribute($param);
    }

    function checkAcl($access = "")
    {
        if (empty($this->config['acl']))
        {
            return true;
        }
        return $this->app['skvn.cms']->checkAcl($this->config['acl'], $access);
    }

    function getInternalCodeAttribute()
    {
        return $this->attributes[$this->codeColumn];
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

    function getViewRefAttribute()
    {
        $id = ($this->id?$this->id:-1);
        return $this->classViewName . "_" . $this->scope . "_" . $id;
    }

    protected function crudFormatValue($pattern, $args = [])
    {
        return vsprintf($pattern, $args);
    }

    /**
     * add id to value
     *
     * @param $val
     * @param array $args
     * @return string
     */
    function crudFormatValueId($val, $args = [])
    {
        return $this->crudFormatValue('%s [%s]', [$val, $this->id]);
    }

    /**
     * Wrap value in <b>
     *
     * @param $val
     * @param array $args
     * @return string
     */
    function crudFormatValueBold($val, $args = [])
    {
        return $this->crudFormatValue('<strong>%s</strong>', [$val]);
    }


    /**
     * Make an activity icon out of the boolean or 1/0 value
     *
     * @param $val
     * @param array $args
     * @return string
     */
    function crudFormatValueActivityIcon($val, $args = [])
    {
        return ( $val ? '<span class="text-succes"><i class="fa fa-check-square-o"></i> '.trans('crud::messages.yes').'</span>':'<span class="text-danger"><i class="fa fa-times-square-o"></i> '.trans('crud::messages.no').'</span>');
    }

    /**
     * Resize attached image
     *
     * @param $val
     * @param array $args
     */
    function crudFormatValueResizedAttach($val, $args = [])
    {
        return '<img src="'.$val->getResizedUrl($args['width'], $args['height']).'" />';
    }

    /**
     * Get All available formatters
     *
     * @return array
     */
    function getAvailFormatters()
    {
        $flist = [];
        $cls = new \ReflectionClass($this);
        $mlist = $cls->getMethods(\ReflectionMethod :: IS_PUBLIC);
        foreach ($mlist as $m)
        {
            if (preg_match("#crudFormatValue([a-zA-Z]+)#", $m->name, $matches))
            {
                $desc = "";
                $c = $m->getDocComment();
                if (!empty($c))
                {
                    $docLines = preg_split('~\R~u', $c);
                    if (isset($docLines[1]))
                    {
                        $desc = trim($docLines[1], "\t *");
                    }
                }
                $flist[] = ['name' => snake_case($matches[1]), 'method' => $m->name, 'description' => $desc];
            }
        }
        return $flist;
    }

    function guessNewKey()
    {
        if (empty($this->guessed_id))
        {
            $this->guessed_id = $this->app['db']->table($this->getTable())->max($this->getKeyName())+1;
        }
        return $this->guessed_id;
    }

    function getParentInstanceId()
    {
        return 0;
    }



}
