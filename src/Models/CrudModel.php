<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\Traits\ModelInjectTrait;
use Skvn\Crud\Traits\ModelConfigTrait;
use Skvn\Crud\Traits\ModelRelationTrait;
use Skvn\Crud\Traits\ModelFilterTrait;
use Skvn\Crud\Traits\ModelFormTrait;
use Skvn\Crud\Exceptions\NotFoundException;

use Illuminate\Container\Container;

abstract class CrudModel extends Model
{
    use ModelInjectTrait;
    use ModelConfigTrait;
    use ModelRelationTrait;
    use ModelFilterTrait;
    use ModelFormTrait;


    const RELATION_BELONGS_TO_MANY = 'belongsToMany';
    const RELATION_BELONGS_TO = 'belongsTo';
    const RELATION_HAS_MANY = 'hasMany';
    const RELATION_HAS_ONE = 'hasOne';

    const DEFAULT_SCOPE = 'default';


    protected $app;
    protected $codeColumn = 'id';

    protected $errors = [];
    protected static $rules = array();
    protected static $messages = array();
    protected $validator;


    public function __construct(array $attributes = array(), $validator = null)
    {
        $this->app = Container :: getInstance();
        $this->bootIfNotBooted();
        $this->preconstruct();
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
            $obj = $class::findOrNew((int)$id);
        }
        else
        {
            $obj = new $class();
        }
        $obj->setScope($scope);
        return $obj;
    }

    static function createSelfInstance($scope = self :: DEFAULT_SCOPE, $id = null)
    {
        $class = get_called_class();
        if (!empty($id))
        {
            $obj = $class::findOrNew((int)$id);
        }
        else
        {
            $obj = new $class();
        }
        $obj->setScope($scope);
        return $obj;
    }

    function getApp()
    {
        return $this->app;
    }

//    public function setCreatedAtAttribute($value)
//    {
//        $type = $this->confParam('timestamps_type');
//        if (!$type || $type == 'int')
//        {
//            if (is_object($value))
//            {
//                $value = $value->timestamp;
//            }
//            else
//            {
//                $value = strtotime($value);
//            }
//        }
//        $this->attributes['created_at'] = $value;
//    }
//
//    public function setUpdatedAtAttribute($value)
//    {
//        $type = $this->confParam('timestamps_type');
//        if (!$type || $type == 'int')
//        {
//            if (is_object($value))
//            {
//                $value = $value->timestamp;
//            }
//            else
//            {
//                $value = strtotime($value);
//            }
//        }
//        $this->attributes['updated_at'] = $value;
//    }

    public function __call($method, $parameters)
    {
        if ($col = $this->getCrudRelation($method))
        {
                return $this->createCrudRelation($col, $method);
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
        if ($this->getCrudRelation($key))
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
        $fld = $this->config['fields'][$key] ?? [];
        if (!empty($fld['field']) && $fld['field'] !== $key)
        {
            return parent :: setAttribute($fld['field'], $value);
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
        $this->setErrors($v->messages()->toArray());
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

    function addError($error)
    {
        $this->errors[] = $error;
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
     * Format date from timestamp
     *
     * @param $val
     * @param array $args
     */
    function crudFormatValueDate($val, $args = [])
    {
        return date($args['format'] ?? 'd.m.Y', $val);
    }


    protected function listPublicMethods($pattern)
    {
        $flist = [];
        $cls = new \ReflectionClass($this);
        $mlist = $cls->getMethods(\ReflectionMethod :: IS_PUBLIC);
        foreach ($mlist as $m)
        {
            if (preg_match($pattern, $m->name, $matches))
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
    /**
     * Get All available formatters
     *
     * @return array
     */
    function getAvailFormatters()
    {
        return $this->listPublicMethods("#crudFormatValue([a-zA-Z]+)#");
    }

    function getAvailOptionGenerators()
    {
        return $this->listPublicMethods("#selectOptions([a-zA-Z]+)#");
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

    function crudExecuteCommand($command, $args = [])
    {
        if (!empty($args['selected_rows']))
        {
            $ids = [];
            foreach ($args['selected_rows'] as $row)
            {
                $ids[] = $row['id'];
            }
            $args['ids'] = $ids;
            if (method_exists($this, $command . 'Bulk'))
            {
                return $this->{$command . 'Bulk'}($args);
            }
            else
            {
                if (!method_exists($this, $command))
                {
                    throw new NotFoundException("Command " . $command . " do not exists on model " . $this->classShortName);
                }
                foreach ($args['ids'] as $id)
                {
                    $obj = static :: findOrFail($id);
                    $obj->$command($args);
                }
                return;
            }
        }
        if (!method_exists($this, $command))
        {
            throw new NotFoundException("Command " . $command . " do not exists on model " . $this->classShortName);
        }
        return $this->$command($args);
    }

}
