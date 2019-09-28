<?php

namespace Skvn\Crud\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Handlers\ListHandler;
use Skvn\Crud\Models\CrudFile;

trait ModelConfigTrait
{
    public $scope = 'default';



    protected $listObj = null;

    /**
     * @param $key
     * @param null $default
     *
     * @return mixed
     *
     * Get config param.
     */
    public function confParam($key, $default = null)
    {
        return Arr :: get($this->config, $key, $default);
    }

    public function getEntityNameAttribute()
    {
        return $this->confParam('ent_name');
    }

    public function setDates($dates)
    {
        if (! is_array($dates)) {
            $dates = [$dates];
        }
        foreach ($dates as $d) {
            if (! in_array($d, $this->dates)) {
                $this->dates[] = $d;
            }
        }
    }

    public function getList()
    {
        if (is_null($this->listObj)) {
            $this->listObj = ListHandler :: create($this, $this->config['scopes'][$this->scope]);
        }

        return $this->listObj;
    }

    public function getScopeParam($key, $default = null)
    {
        return $this->config['scopes'][$this->scope][$key] ?? $default;
    }

    public function getTreeConfig($prop = '')
    {
        if (isset($this->config['tree'])) {
            if (! empty($prop)) {
                if (isset($this->config['tree'][$prop])) {
                    return $this->config['tree'][$prop];
                }
            } else {
                return $this->config['tree'];
            }
        }

        return false;
    }

    public function isTree()
    {
        return ! empty($this->config['tree'])/* && !$this->getTreeConfig('use_list')*/;
    }

    public function setScope($scope = null)
    {
        if (is_null($scope) || $scope == 'null') {
            $this->scope = self :: DEFAULT_SCOPE;
        } else {
            $this->scope = $scope;
        }
        $method = Str::camel('get_scope_config_'.$scope);
        if (method_exists($this, $method)) {
            $this->config['scopes'][$this->scope] = array_merge($this->config['scopes'][$this->scope] ?? [], $this->$method());
        }
        if (empty($this->config['scopes'][$this->scope])) {
            if ($this->scope != self :: DEFAULT_SCOPE) {
                throw new ConfigException('Unknown scope '.$scope.' for model '.$this->classViewName);
            }
            $this->config['scopes'][$this->scope] = ['title' => 'Autogenerated'];
        }
        $this->config['scope'] = $this->scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public static function fileParams()
    {
        return [];
    }

    public function getFilesConfig($name, $param = null)
    {
        if (! isset($this->config['file_params'][$name])) {
            $conf = [
                'path'        => '%l1'.DIRECTORY_SEPARATOR.'%l2',
                'inline_path' => 'images'.DIRECTORY_SEPARATOR.'%tbl'.DIRECTORY_SEPARATOR.'%id',
                'class'       => CrudFile :: class,
                'prefix'      => 'img',
                'inline_url'  => '',
                'inline_root' => public_path(),
                'table'       => $this->getTable(),
            ];
            $conf = array_merge($conf, static :: fileParams());
            $conf['instance_id'] = $this->exists ? $this->getKey() : $this->guessNewKey();

            $md5 = md5($name);
            $replace = [
                '%l1'  => substr($md5, 0, 2),
                '%l2'  => substr($md5, 2, 2),
                '%i1'  => $conf['instance_id'] % 10,
                '%i3'  => str_pad($conf['instance_id'] % 1000, 3, '0', STR_PAD_LEFT),
                '%id'  => $conf['instance_id'],
                '%p1'  => $this->getParentInstanceId() % 10,
                '%p3'  => str_pad($this->getParentInstanceId() % 1000, 3, '0', STR_PAD_LEFT),
                '%pid' => $this->getParentInstanceId(),
                '%tbl' => $conf['table'],
            ];
            $to_replace = ['path', 'inline_path'];
            foreach ($to_replace as $r) {
                foreach ($replace as $k => $v) {
                    $conf[$r] = str_replace($k, $v, $conf[$r]);
                }
            }
            $this->config['file_params'][$name] = $conf;
        }

        if (! empty($param)) {
            return isset($this->config['file_params'][$name][$param]) ? $this->config['file_params'][$name][$param] : false;
        } else {
            return $this->config['file_params'][$name];
        }
    }

    public function resolveView($view)
    {
        $hints = $this->app['view']->getFinder()->getHints();
        $key = 'crud.'.$this->classViewName.'.'.$this->getScope();
        $source = isset($hints[$key]) ? $hints[$key] : [];
        if (empty($source)) {
            $target = [];
            $add = [
                '/crud',
                '/crud/models',
                '/crud/models/'.$this->classViewName,
                '/crud/models/'.$this->classViewName.'/'.$this->getScope(),
            ];
            foreach ($this->app['config']['view.paths'] as $path) {
                if (isset($hints['crud'])) {
                    foreach ($hints['crud'] as $entry) {
                        if (! in_array($entry, $target)) {
                            $target[] = $entry;
                        }
                    }
                }
                if (! in_array($path, $target)) {
                    $target[] = $path;
                }
                foreach ($add as $entry) {
                    $tpath = $path.$entry;
                    if (! in_array($tpath, $source)) {
                        array_unshift($target, $tpath);
                    }
                }
            }
            if (! empty($target)) {
                $this->app['view']->getFinder()->prependNamespace($key, $target);
            }
        }

        return $key.'::'.$view;
    }

//
}
