<?php

namespace Skvn\Crud\Models;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Skvn\Crud\Exceptions\NotFoundException;
use Skvn\Crud\Exceptions\ValidationException;
//use Skvn\Crud\Traits\ModelRelationTrait;
//use Skvn\Crud\Traits\ModelFilterTrait;
use Skvn\Crud\Traits\ModelConfigTrait;
use Skvn\Crud\Traits\ModelFormTrait;
use Skvn\Crud\Traits\ModelInjectTrait;

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

    protected $validationErrors = [];
    protected $validationRules = [];
    protected $validationMessages = [];
    public $timestamps = false;
    public $crudRelations;



    /**
     * Flag for tracking created_by and updated_by attributes.
     *
     * @var bool
     */
    protected $trackAuthors = false;

    public function __construct(array $attributes = [])
    {
        $this->app = Container :: getInstance();
        $this->bootIfNotBooted();

        $this->classShortName = class_basename($this);
        $this->classViewName = snake_case($this->classShortName);
        $this->config = $this->app['config']->get('crud.'.(! empty($this->table) ? $this->table : $this->classViewName));
        $this->config['class_name'] = $this->classViewName;
        if (empty($this->table)) {
            $this->table = $this->config['table'] ?? $this->classViewName;
        }
        $this->config['file_params'] = [];

        if (! empty($this->config['fields'])) {
            foreach ($this->config['fields'] as $name => $col) {
                $this->config['fields'][$name] = $this->configureField($name, $col);
            }
        }

        $this->preconstruct();
        parent::__construct($attributes);
        $this->crudRelations = new Relations($this);

        $this->postconstruct();
    }

    public static function resolveClass($model)
    {
        $app = Container :: getInstance();

        return $app['config']['crud_common.model_namespace'].'\\'.studly_case($model);
    }

    public static function createInstance($model, $scope = self :: DEFAULT_SCOPE, $id = null)
    {
        $class = static :: resolveClass($model);
        if (! empty($id)) {
            $obj = $class::findOrNew((int) $id);
        } else {
            $obj = new $class();
        }
        $obj->setScope($scope);

        return $obj;
    }

    public static function createSelfInstance($scope = self :: DEFAULT_SCOPE, $id = null)
    {
        $class = get_called_class();
        if (! empty($id)) {
            $obj = $class::findOrNew((int) $id);
        } else {
            $obj = new $class();
        }
        $obj->setScope($scope);

        return $obj;
    }


    function save(array $options = [])
    {
        $saved = parent :: save($options);
        if ($saved)
        {
            $saved = $this->crudRelations->save();
        }
        return $saved;
    }

    public function saveRelations($name = null)
    {
        return $this->crudRelations->save($name);
    }

    public function getApp()
    {
        return $this->app;
    }

    public function __call($method, $parameters)
    {
        if ($this->crudRelations->has($method)) {
            return $this->crudRelations->getRelation($method);
        }

        return parent::__call($method, $parameters);
    }

    public function __isset($key)
    {
        $col = $this->config['fields'][$key] ?? [];
        if (! empty($col['fields'])) {
            foreach ($this->config['fields'][$key]['fields'] as $f) {
                if (parent :: __isset($f)) {
                    return true;
                }
            }
        }
        if (! empty($col['field']) && $col['field'] !== $key) {
            return parent :: __isset($col['field']);
        }

        return parent :: __isset($key);
    }

    public function getAttribute($key)
    {
        if ($this->crudRelations->has($key)) {
            return $this->crudRelations->get($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($this->crudRelations->has($key)) {
            $this->crudRelations[$key]->set($value);

            return;
        }
        if ($this->callSetters($key, $value) === true) {
            return;
        }
        $fld = $this->config['fields'][$key] ?? [];
        if (! empty($fld['field']) && $fld['field'] !== $key) {
            return parent :: setAttribute($fld['field'], $value);
        }

        return parent :: setAttribute($key, $value);
    }

    public function getTitle()
    {
        $param = $this->confParam('title_field', 'title');

        return $this->getAttribute($param);
    }

    public function getSelfAttribute()
    {
        return $this;
    }

    public function getMorphClass()
    {
        if ($this->app['config']->get('crud_common.replace_morph_classes_with_basename')) {
            return $this->classShortName;
        }

        return parent :: getMorphClass();
    }

    public function getActualClassNameForMorph($class)
    {
        if ($this->app['config']->get('crud_common.replace_morph_classes_with_basename')) {
            return self :: resolveClass($class);
        }

        return parent :: getActualClassNameForMorph($class);
    }

    public function checkAcl($access = '')
    {
        if (empty($this->config['acl'])) {
            return true;
        }

        return $this->app['skvn.cms']->checkAcl($this->config['acl'], $access);
    }

    public function getInternalCodeAttribute()
    {
        return $this->attributes[$this->codeColumn];
    }

    protected function getValidationMessage($field, $rule, $message = "")
    {
        if (!empty($message))
        {
            return $message;
        }
        if (isset($this->validationMessages[$field . '.*']))
        {
            return $this->validationMessages[$field . '.*'];
        }
        if (isset($this->validationMessages[$field . '.' . $rule]))
        {
            return $this->validationMessages[$field . '.' . $rule];
        }
        if (isset($this->validationMessages[$rule]))
        {
            return $this->validationMessages[$rule];
        }
        return $this->app['translator']->trans('crud::rules.' . $rule);
    }

    protected function parseValidationRule($field, $rule)
    {
        if (strpos($rule, ":") !== false)
        {
            $parts = explode(":", $rule);
            $r = $parts[0];
            if (strpos($parts[1], ",") !== false)
            {
                $p = explode(",", $parts[1]);
            }
            else
            {
                $p = !empty($parts[1]) ? [$parts[1]] : [];
            }
            $m = $this->getValidationMessage($field, $r, $parts[2] ?? "");
        }
        else
        {
            $r = $rule;
            $p = [];
            $m = $this->getValidationMessage($field, $rule);
        }
        switch ($r)
        {
            case 'unique':
                if (empty($p))
                {
                    $p[] = $this->getTable();
                    $p[] = $field;
                    if ($this->exists)
                    {
                        $p[] = $this->getKey();
                    }
                }
            break;
        }
        return ['rule' => $r, 'params' => $p, 'message' => $m];
    }

    protected function appendValidationRule(&$rules, &$messages, $field, $rule)
    {
        //var_dump($field);
        //var_dump($rule);
        $parsed = $this->parseValidationRule($field, $rule);
        //var_dump($parsed);
        $parts = isset($rules[$field]) ? explode("|", $rules[$field]) : [];
        $parts[] = $parsed['rule'] . (!empty($parsed['params']) ? ':' : '') . implode(",", $parsed['params']);
        $rules[$field] = implode("|", $parts);
        if (!isset($messages[$field . "." . $parsed['rule']]))
        {
            $messages[$field . "." . $parsed['rule']] = $parsed['message'];
        }
    }


    function createValidator()
    {
        $rules = [];
        $messages = [];
        foreach ($this->validationRules as $field => $rule_list)
        {
            foreach (explode("|", $rule_list) as $rule)
            {
                $this->appendValidationRule($rules, $messages, $field, $rule);
            }
        }
        foreach ($this->config['fields'] ?? [] as $field => $conf)
        {
            if (!empty($conf['validators']))
            {
                foreach (explode("|", $conf['validators']) as $rule)
                {
//                    $this->appendValidationRule($rules, $messages, $field, $rule);
                    if (!empty($conf['field']) && empty($conf['relation']))
                    {
                        $this->appendValidationRule($rules, $messages, $conf['field'], $rule);
                    }
                    else
                    {
                        $this->appendValidationRule($rules, $messages, $field, $rule);
                    }
                }
            }
        }
        $data = array_merge($this->attributes, $this->crudRelations->getAll());
        //var_dump($data);
        //var_dump($rules);
        return $this->app['validator']->make($data, $rules, $messages);
    }

    public function validate($throw = false)
    {
        $v = $this->createValidator();
        if ($v->passes())
        {
            return true;
        }

        $this->setErrors($v->messages()->toArray());

        if ($throw)
        {
            throw new ValidationException(json_encode($v->messages()->toArray()));
        }

        return false;
    }

    protected function setErrors($errors)
    {
        $this->validationErrors = $errors;
    }

    public function getErrors()
    {
        return array_merge($this->validationErrors, $this->crudRelations->getErrors());
    }

    public function addError($field, $error)
    {
        if (!isset($this->validationErrors[$field]))
        {
            $this->validationErrors[$field] = [];
        }
        $this->validationErrors[$field][] = $error;
    }

    function transferErrors(CrudModel $model, $prefix)
    {
        foreach ($model->getErrors() as $fld => $errors)
        {
            foreach ($errors as $error)
            {
                $this->addError($prefix . "." . $fld, $error);
            }
        }
    }

    public function hasErrors()
    {
        return ! empty($this->validationErrors);
    }

    public function getViewRefAttribute()
    {
        $id = ($this->id ? $this->id : -1);

        return $this->classViewName.'_'.$this->scope.'_'.$id;
    }

    protected function crudFormatValue($pattern, $args = [])
    {
        return vsprintf($pattern, $args);
    }

    /**
     * add id to value.
     *
     * @param $val
     * @param array $args
     *
     * @return string
     */
    public function crudFormatValueId($val, $args = [])
    {
        return $this->crudFormatValue('%s [%s]', [$val, $this->id]);
    }

    /**
     * Wrap value in <b>.
     *
     * @param $val
     * @param array $args
     *
     * @return string
     */
    public function crudFormatValueBold($val, $args = [])
    {
        return $this->crudFormatValue('<strong>%s</strong>', [$val]);
    }

    /**
     * Email value as mailto link.
     *
     * @param $val
     * @param array $args
     *
     * @return string
     */
    public function crudFormatValueMailto($val, $args = [])
    {
        return $this->crudFormatValue('<a href="mailto:%s">%s</a>', [$val, $val]);
    }

    /**
     * Make an activity icon out of the boolean or 1/0 value.
     *
     * @param $val
     * @param array $args
     *
     * @return string
     */
    public function crudFormatValueActivityIcon($val, $args = [])
    {
        return  $val ? '<span class="text-succes"><i class="fa fa-check-square-o"></i> '.trans('crud::messages.yes').'</span>' : '<span class="text-danger"><i class="fa fa-times-square-o"></i> '.trans('crud::messages.no').'</span>';
    }

    /**
     * Resize attached image.
     *
     * @param $val
     * @param array $args
     */
    public function crudFormatValueResizedAttach($val, $args = [])
    {
        return '<img src="'.$val->getResizedUrl($args['width'], $args['height']).'" />';
    }

    /**
     * Format date from timestamp or Carbon instance.
     *
     * @param $val
     * @param array $args
     */
    public function crudFormatValueDate($val, $args = [])
    {
        if (is_int($val)) {
            return date($args['format'] ?? 'd.m.Y', $val);
        } elseif ($val instanceof \Carbon\Carbon) {
            return $val->format($args['format'] ?? 'd.m.Y');
        }
    }

    /**
     * Remove tags and truncate to $args[length].
     *
     * @param $val
     * @param array $args
     */
    public function crudFormatValueTruncate($val, $args = [])
    {
        $val = strip_tags($val);
        if (! empty($args['length'])) {
            if (mb_strlen($val) > $args['length']) {
                $val = mb_substr($val, 0, $args['length']).'...';
            }
        }

        return $val;
    }

    protected function listPublicMethods($pattern)
    {
        $flist = [];
        $cls = new \ReflectionClass($this);
        $mlist = $cls->getMethods(\ReflectionMethod :: IS_PUBLIC);
        foreach ($mlist as $m) {
            if (preg_match($pattern, $m->name, $matches)) {
                $desc = '';
                $c = $m->getDocComment();
                if (! empty($c)) {
                    $docLines = preg_split('~\R~u', $c);
                    if (isset($docLines[1])) {
                        $desc = trim($docLines[1], "\t *");
                    }
                }
                $flist[] = ['name' => snake_case($matches[1]), 'method' => $m->name, 'description' => $desc];
            }
        }

        return $flist;
    }

    /**
     * Get All available formatters.
     *
     * @return array
     */
    public function getAvailFormatters()
    {
        return $this->listPublicMethods('#crudFormatValue([a-zA-Z]+)#');
    }

    /**
     * Get all available select options providers.
     *
     * @return array
     */
    public function getAvailOptionGenerators()
    {
        return $this->listPublicMethods('#selectOptions([a-zA-Z]+)#');
    }

    /**
     * Get all available scopes.
     *
     * @return array
     */
    public function getAvailScopes()
    {
        return $this->listPublicMethods('#scope([a-zA-Z]+)#');
    }

    public function guessNewKey()
    {
        if (empty($this->guessed_id)) {
            $this->guessed_id = $this->app['db']->table($this->getTable())->max($this->getKeyName()) + 1;
        }

        return $this->guessed_id;
    }

    public function getParentInstanceId()
    {
        return 0;
    }

    public function crudExecuteCommand($command, $args = [])
    {
        if (! empty($args['selected_rows'])) {
            $ids = [];
            foreach ($args['selected_rows'] as $row) {
                $ids[] = $row['id'];
            }
            $args['ids'] = $ids;
            if (method_exists($this, $command.'Bulk')) {
                return $this->{$command.'Bulk'}($args);
            } else {
                if (! method_exists($this, $command)) {
                    throw new NotFoundException('Command '.$command.' do not exists on model '.$this->classShortName);
                }
                foreach ($args['ids'] as $id) {
                    $obj = static :: findOrFail($id);
                    $obj->$command($args);
                }

                return;
            }
        }
        if (! method_exists($this, $command)) {
            throw new NotFoundException('Command '.$command.' do not exists on model '.$this->classShortName);
        }

        return $this->$command($args);
    }

    public function deleteAttachedModel($args)
    {
        $rel = $args['delete_attach_rel'] ?? false;
        if ($rel) {
            if (empty($args['delete_attach_id'])) {
                $args['delete_attach_id'] = -1;
            }
            $this->crudRelations[$rel]->delete($args['delete_attach_id']);
        }
    }

    public function appendConditions($conditions)
    {
        return $conditions;
    }

    public function preApplyConditions($coll, $conditions)
    {
        return $conditions;
    }

    /**
     * Default model scope.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeDefault($query)
    {
        return $query;
    }

    public function newCollection(array $models = [])
    {
        $class = $this->app['config']['crud_common.collection_class'];
        return new $class($models);
    }

}
