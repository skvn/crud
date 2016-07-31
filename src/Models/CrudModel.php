<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\Traits\ModelInjectTrait;
use Skvn\Crud\Traits\ModelConfigTrait;
//use Skvn\Crud\Traits\ModelRelationTrait;
//use Skvn\Crud\Traits\ModelFilterTrait;
use Skvn\Crud\Traits\ModelFormTrait;
use Skvn\Crud\Exceptions\NotFoundException;

use Illuminate\Container\Container;

abstract class CrudModel extends Model
{
    use ModelInjectTrait;
    use ModelConfigTrait;
    use ModelFormTrait;



    const DEFAULT_SCOPE = 'default';


    protected $app;
    protected $codeColumn = 'id';

    protected $config;
    public $classShortName;
    public $classViewName;


    private $guessed_id = 0;

    protected $errors = [];
    protected static $rules = array();
    protected static $messages = array();
    protected $validator;
    public $timestamps = false;
    //protected $eventsDisabled = false;
    public $crudRelations;


    /**
     * Flag for tracking created_by and updated_by attributes
     * @var bool
     */
    public $trackAuthors = false;


    public function __construct(array $attributes = array())
    {
        $this->app = Container :: getInstance();
        $this->bootIfNotBooted();

        $this->classShortName = class_basename($this);
        $this->classViewName = snake_case($this->classShortName);
        $this->config = $this->app['config']->get('crud.'.(!empty($this->table) ? $this->table : $this->classViewName));
        $this->config['class_name'] = $this->classViewName;
        if (empty($this->table))
        {
            $this->table = $this->config['table'] ?? $this->classViewName;
        }
        $this->config['file_params'] = [];

        if (!empty($this->config['fields']))
        {
            foreach ($this->config['fields'] as $name => $col)
            {
                $this->config['fields'][$name] = $this->configureField($name, $col);
            }
        }

        $this->preconstruct();
        parent::__construct($attributes);
        $this->crudRelations = new Relations($this);

        $this->postconstruct();

        $this->validator = $this->app['validator'];
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


//    function saveDirect()
//    {
//        //$this->eventsDisabled = true;
//        $result = parent :: save();
//        //$this->eventsDisabled = false;
//        return $result;
//    }

//    function saveFull()
//    {
//        if ($this->save())
//        {
//            return $this->crudRelations->save();
//        }
//        return false;
//    }

    function saveRelations()
    {
        return $this->crudRelations->save();
    }


    function getApp()
    {
        return $this->app;
    }


    public function __call($method, $parameters)
    {
        if ($this->crudRelations->has($method))
        {
            return $this->crudRelations->getRelation($method);
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
        if ($this->crudRelations->has($key))
        {
            return $this->crudRelations->getAny($key);
        }

        return parent::getAttribute($key);
    }

    function setAttribute($key, $value)
    {
        if ($this->crudRelations->has($key))
        {
            $this->crudRelations[$key]->set($value);
            return;
        }
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

    function getSelfAttribute()
    {
        return $this;
    }

    public function getMorphClass()
    {
        if ($this->app['config']->get('crud_common.replace_morph_classes_with_basename'))
        {
            return $this->classShortName;
        }
        return parent :: getMorphClass();
    }

    public function getActualClassNameForMorph($class)
    {
        if ($this->app['config']->get('crud_common.replace_morph_classes_with_basename'))
        {
            return self :: resolveClass($class);
        }
        return parent :: getActualClassNameForMorph($class);
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
     * Email value as mailto link
     *
     * @param $val
     * @param array $args
     * @return string
     */
    function crudFormatValueMailto($val, $args = [])
    {
        return $this->crudFormatValue('<a href="mailto:%s">%s</a>', [$val, $val]);
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

    /**
     * Remove tags and truncate to $args[length]
     *
     * @param $val
     * @param array $args
     */
    function crudFormatValueTruncate($val, $args = [])
    {
        $val = strip_tags($val);
        if (!empty($args['length']))
        {
            if (mb_strlen($val) > $args['length'])
            {
                $val = mb_substr($val, 0, $args['length']) . "...";
            }
        }
        return $val;
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

    /**
     * Get all available select options providers
     *
     * @return array
     */
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

    public function deleteAttachedModel($args)
    {
        $rel = $args['delete_attach_rel'] ?? false;
        if ($rel)
        {
            if (empty($args['delete_attach_id']))
            {
                $args['delete_attach_id'] = -1;
            }
            $this->crudRelations[$rel]->delete($args['delete_attach_id']);
        }
    }

    public  function appendConditions($conditions)
    {
        return $conditions;
    }

    public  function preApplyConditions($coll, $conditions)
    {
        return $conditions;
    }



}
