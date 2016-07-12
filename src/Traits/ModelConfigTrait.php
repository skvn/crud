<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Form\Form;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudFile;
use Skvn\Crud\Handlers\ListHandler;
use Illuminate\Support\Arr;


trait ModelConfigTrait
{

    protected $config;
    public $classShortName;
    public $classViewName;
    public $scope = "default";


    
    protected $listObj = null;



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

        $this->config['file_params'] = [];
    }



    function getFieldsByField()
    {
        $list = [];
        foreach ($this->config['fields'] as $fld)
        {
            $list[$fld['field']] = $fld;
        }
        return $list;
    }

    function getField($name, $throw = false)
    {
        $field = $this->config['fields'][$name] ?? [];
        if (empty($field) && $throw)
        {
            throw new ConfigException('Field ' . $name . ' on ' . $this->classShortName . ' do not exist');
        }
        $field['name'] = $name;
        return $field;
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
        return Arr :: get($this->config, $key, $default);
    }

    function getList()
    {
        if (is_null($this->listObj))
        {
            $this->listObj = ListHandler :: create($this, $this->config['scopes'][$this->scope]);
        }
        return $this->listObj;
    }

    function scopeParam($key, $default = null)
    {
        return $this->config['scopes'][$this->scope][$key] ?? $default;
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
        return !empty($this->config['tree'])/* && !$this->getTreeConfig('use_list')*/;
    }

//    function getDescribedColumnValue($col, $format = false, $format_args = [])
//    {
//        $value = null;
//        if ($relSpl = $this->crudRelations->resolveReference($col))
//        {
//            $rel = $relSpl['rel'];
//            $attr = $relSpl['attr'];
//            try
//            {
//                $relObj = $this->$rel;
//                $value = is_object($relObj) ? $relObj->$attr : "";
//                if (!empty($format))
//                {
//                    $method = "crudFormatValue" . camel_case($format);
//                    if (method_exists($relObj, $method))
//                    {
//                        $value = $relObj->$method($value, $format_args);
//                    }
//                }
//            }
//            catch (\Exception $e)
//            {
//                $value = "(not found)" . $e->getMessage() . ":" . $e->getFile() . ":" . $e->getLine();
//            }
//        }
//        else
//        {
//            if ($this->__isset($col))
//            {
//                $form_config = $this->getField($col);
//                if ($form_config && !empty($form_config['type']))
//                {
//                    $form_config['name'] = $col;
//                    $field = Form :: createControl($this, $form_config);
//                    $value = $field->getOutputValue();
//                }
//                else
//                {
//                    $value = $this->$col;
//                }
//                if (!empty($format))
//                {
//                    $method = "crudFormatValue" . camel_case($format);
//                    if (method_exists($this, $method))
//                    {
//                        $value = $this->$method($value, $format_args);
//                    }
//                }
//            }
//        }
//        return $value;
//    }


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
    }

    function getScope()
    {
        return $this->scope;
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