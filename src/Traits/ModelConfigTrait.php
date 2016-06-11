<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Form\Form;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudFile;
use Skvn\Crud\Handlers\ListHandler;


trait ModelConfigTrait
{

    protected $config;
    public $classShortName;
    public $classViewName;
    public $scope = "default";

    protected $list_prefs = null;

    /* Flag for tracking created_by  and updated_by */
    protected $track_authors = false;
    protected $listObj = null;

    private $guessed_id = 0;


    public static function bootModelConfigTrait()
    {
        static::registerPreconstruct(function($instance){
            $instance->initConfig();
        });
    }


    protected function initConfig()
    {
        $this->classShortName = class_basename($this);
        $this->classViewName = snake_case($this->classShortName);
        $this->config = $this->app['config']->get('crud.'.(!empty($this->table) ? $this->table : $this->classViewName));
        $this->config['class_name'] = $this->classViewName;
        if (!empty($this->config['fields']))
        {
            foreach ($this->config['fields'] as $name => $col)
            {
                if (empty($col['field']))
                {
                    $col['field'] = $name;
                }
                if (!empty($col['hint_default']) && !empty($col['hint']) &&  $col['hint'] === 'auto')
                {
                    $col['hint'] = $this->classShortName.'_fields_'.$name;
                }
                $this->config['fields'][$name] = $col;
            }
        }

        if (empty($this->table))
        {
            $this->table = $this->config['table'] ?? $this->classViewName;
        }
        $this->timestamps = $this->config['timestamps'] ?? false;
        if (isset($this->config['authors']))
        {
            $this->track_authors = $this->config['authors'];
        }

        $this->config['file_params'] = [];
    }



    public function getFields($prop='')
    {
        $form =  $this->confParam('fields');

        return $prop ? (isset($form[$prop]) ? $form[$prop] : null) : $form;
    }

    function getField($name)
    {
        return $this->getFields($name);
    }

    /**
     * @param $key
     * @param null $default     
     * @return mixed
     *
     * Get config param.
     *
     */
    public function confParam($key, $default = null)
    {
        
        if (strpos($key, '.') === false) {
            return (!empty($this->config[$key]) ? $this->config[$key] : $default);
        } else {
            return $this->app['config']->get('crud.' . $this->getTable() . '.' . $key, $default);
        }
        
    }

    function getList()
    {
        if (is_null($this->listObj))
        {
            $this->listObj = ListHandler :: create($this, $this->config['scopes'][$this->scope]);
        }
        return $this->listObj;
    }

    public function getListConfig($prop='')
    {
        return $this->getList()->getParam($prop);
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

    function getDescribedColumnValue($col, $format = false, $format_args = [])
    {
        $value = null;
        if ($relSpl = $this->resolveListRelation($col))
        {
            $rel = $relSpl[0];
            $attr = $relSpl[1];
            if (method_exists($this, 'hasAttach') && $this->hasAttach($rel))
            {
                $value = $this->getAttach($rel)->$attr;
            }
            else
            {
                try
                {
                    $relObj = $this->$rel;
                    $value = $relObj->$attr;
                    if (!empty($format))
                    {
                        $method = "crudFormatValue" . camel_case($format);
                        if (method_exists($relObj, $method))
                        {
                            $value = $relObj->$method($value, $format_args);
                        }
                    }
                }
                catch (\Exception $e)
                {
                    $value = "(not found)" . $e->getMessage() . ":" . $e->getFile() . ":" . $e->getLine();
                }
            }
        }
        else
        {
            if ($this->__isset($col))
            {
                $form_config = $this->getField($col);
                if ($form_config && !empty($form_config['type']))
                {
                    $form_config['name'] = $col;
                    $field = Form :: createControl($this, $form_config);
                    $value = $field->getOutputValue();
                }
                else
                {
                    $value = $this->$col;
                }
                if (!empty($format))
                {
                    $method = "crudFormatValue" . camel_case($format);
                    if (method_exists($this, $method))
                    {
                        $value = $this->$method($value, $format_args);
                    }
                }
            }
        }
        return $value;
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
        $method = camel_case("get_scope_config_" . $scope);
        if (method_exists($this, $method))
        {
            $this->config['scopes'][$this->scope] = array_merge($this->config['scopes'][$this->scope] ?? [], $this->$method());
        }
        if (empty($this->config['scopes'][$this->scope]))
        {
            if ($this->scope != self :: DEFAULT_SCOPE)
            {
                throw new ConfigException('Unknown scope ' . $scope . ' for model ' . $this->classViewName);
            }
            $this->config['scopes'][$this->scope] = ['title' => "Autogenerated"];
        }
        $this->config['scope'] = $this->scope;
//        if (!empty($this->config['fields']))
//        {
//            $form = !empty($this->config['scopes'][$this->scope]['form']) ? $this->flatFields($this->config['scopes'][$this->scope]['form'], !empty($this->config['scopes'][$this->scope]['form_tabbed'])) : [];
//            if (!empty($this->config['fields']))
//            {
//                foreach ($this->config['fields'] as $name => $field)
//                {
//                    if (in_array($name, $form))
//                    {
//                        $this->markFillable($name, $field);
//                    }
//                }
//            }
//        }
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
        //$form = $this->confParam('list.' . $this->scope . '.filter');
        //FIXME: backward compability
        $form = $this->getListConfig("filter");
        if (empty($form))
        {
            $form = [];
            foreach ($this->getListConfig("list") as $column)
            {
                if (!empty($column['filterable']))
                {
                    $form[] = $column['data'];
                }
            }
        }

        //FIXME: WTF ?
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



    static function fileParams()
    {
        return [];
    }


    function getFilesConfig($name, $param = null)
    {
        if (!isset($this->config['file_params'][$name]))
        {
            $conf = [
                'path' => "%l1" . DIRECTORY_SEPARATOR . "%l2",
                'inline_path' => "images" . DIRECTORY_SEPARATOR . "%tbl" . DIRECTORY_SEPARATOR . "%id",
                'class' => CrudFile :: class,
                'prefix' => "img",
                'inline_url' => "",
                'inline_root' => public_path(),
                'table' => $this->getTable()
            ];
            $conf = array_merge($conf, static :: fileParams());
            $conf['instance_id'] = $this->exists ? $this->getKey() : $this->guessNewKey();
            //$obj = new $conf['class']();
            //$conf['table'] = $obj->getTable();

            $md5 = md5($name);
            $replace = [
                '%l1' => substr($md5,0,2),
                '%l2' => substr($md5,2,2),
                '%i1' => $conf['instance_id'] % 10,
                '%i3' => str_pad($conf['instance_id'] % 1000, 3, '0', STR_PAD_LEFT),
                '%id' => $conf['instance_id'],
                '%p1' => $this->getParentInstanceId() % 10,
                '%p3' => str_pad($this->getParentInstanceId() % 1000, 3, '0', STR_PAD_LEFT),
                '%pid' => $this->getParentInstanceId(),
                '%tbl' => $conf['table']
            ];
            $to_replace = ['path', 'inline_path'];
            foreach ($to_replace as $r)
            {
                foreach ($replace as $k => $v)
                {
                    $conf[$r] = str_replace($k, $v, $conf[$r]);
                }
            }
            $this->config['file_params'][$name] = $conf;
        }

        if (!empty($param))
        {
            return isset($this->config['file_params'][$name][$param]) ? $this->config['file_params'][$name][$param] : false;
        }
        else
        {
            return $this->config['file_params'][$name];
        }
    }


    function resolveView($view)
    {
        $hints = $this->app['view']->getFinder()->getHints();
        $key = "crud." . $this->classViewName . "." . $this->getScope();
        $source = isset($hints[$key]) ? $hints[$key] : [];
        if (empty($source))
        {
            $target = [];
            $add = [
                '/crud',
                '/crud/models',
                '/crud/models/' . $this->classViewName,
                '/crud/models/' . $this->classViewName . '/' . $this->getScope(),
            ];
            foreach ($this->app['config']['view.paths'] as $path)
            {
                if (isset($hints['crud']))
                {
                    foreach ($hints['crud'] as $entry)
                    {
                        if (!in_array($entry, $target))
                        {
                            $target[] = $entry;
                        }
                    }
                }
                if (!in_array($path, $target))
                {
                    $target[] = $path;
                }
                foreach ($add as $entry)
                {
                    $tpath = $path . $entry;
                    if (!in_array($tpath, $source))
                    {
                        array_unshift($target, $tpath);
                    }
                }
            }
            if (!empty($target))
            {
                $this->app['view']->getFinder()->prependNamespace($key, $target);
            }
        }
        return $key . "::" . $view;
    }//

    


}