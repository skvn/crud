<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Form\Field;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Models\CrudFile;


trait ModelConfigTrait
{

    protected $config;
    public $classShortName;
    public $classViewName;
    public $scope = "default";

    protected $list_prefs = null;

    /* Flag for tracking created_by  and updated_by */
    protected $track_authors = false;

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
        $this->config = $this->app['config']->get('crud.crud_'.(!empty($this->table) ? $this->table : $this->classViewName));
        $this->config['class_name'] = $this->classViewName;
        if (!empty($this->config['fields']))
        {
            $form = !empty($this->config['form']) ? $this->flatFields($this->config['form'], !empty($this->config['form_tabbed'])) : [];
            foreach ($this->config['fields'] as $name => $col)
            {

                if (!empty($col['hint_default']) && !empty($col['hint']) &&  $col['hint'] === 'auto')
                {
                    $this->config['fields'][$name]['hint'] = $this->classShortName.'_fields_'.$name;
                }
                //fill relations
                if (isset($col['relation']))
                {
                    $rel_name = !empty($this->config['fields'][$name]['relation_name']) ? $this->config['fields'][$name]['relation_name'] : $name;
                    $this->crudRelations[$rel_name] = $col['relation'];
                }

                //if field in form - make it fillable or processable as relation
                if (in_array($name, $form) || !empty($col['fillable']))
                {
                    $this->markFillable($name, $col);
                }
            }
        }

        if (empty($this->table))
        {
            $this->table = isset($this->config['table']) ? $this->config['table'] : $this->classViewName;
        }

        $this->timestamps = isset($this->config['timestamps']) ? $this->config['timestamps'] : false;

        if (isset($this->config['authors']))
        {
            $this->track_authors = $this->config['authors'];
        }

        if ($this->isTree())
        {
//            $this->fillable[] = $this->columnTreePid;
//            $this->fillable[] = $this->columnTreeOrder;
//            $this->fillable[] = $this->columnTreePath ;
//            $this->fillable[] = $this->columnTreeDepth;
            $this->fillable[] = $this->config['tree']['pid_column'];
            $this->fillable[] = $this->config['tree']['order_column'];
            $this->fillable[] = $this->config['tree']['path_column'] ;
            $this->fillable[] = $this->config['tree']['depth_column'];
        }

        $this->config['file_params'] = [];


    }


    private function markFillable($name, $col)
    {
        if (isset($col['relation']) &&
            ($col['relation'] == 'belongsToMany' || $col['relation'] == 'hasMany' || $col['relation'] == 'hasOne')

        ) {
            if ($col['relation'] != 'hasOne')
            {
                $this->config['fields'][$name]['multiple'] = 1;
            }

            //Add multi file to fillable since it is handled by fill not by post save relations
            if ($col['type'] != 'multi_file')
            {
                $this->processableRelations[$name] = $col['relation'];
            }
            else
            {
                $this->fillable[] = $name;
            }
            return;

        }
        else
        {
            if (!empty($col['fields']))
            {
                foreach ($col['fields'] as $f)
                {
                    $this->fillable[] = $f;
                }
            }
            else
            {
                $this->fillable[] = $name;
            }
        }

    }

    private function flatFields($fields, $tabbed)
    {
        if ($tabbed)
        {
            $cols = [];
            foreach ($fields as $tab => $flist)
            {
                $cols = array_merge($cols, $flist);
            }
            return $cols;
        }
        return $fields;
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
     * @param bool $use_scope
     * @return mixed
     *
     * Get config param. If $use_scope === true, look first in list.CURRENT_SCOPE
     *
     */
    public function confParam($key, $default = null, $use_scope=true)
    {
        if (!$use_scope) {
            if (strpos($key, '.') === false) {
                return (!empty($this->config[$key]) ? $this->config[$key] : $default);
            } else {
                return $this->app['config']->get('crud.crud_' . $this->getTable() . '.' . $key, $default);
            }
        } else {

            $original_key = $key;
            $key = 'scopes.' . $this->scope . '.' . $key;
            $val = $this->app['config']->get('crud.crud_' . $this->getTable() . '.' . $key);
            if (is_null($val))
            {
                return $this->confParam($original_key, $default, false);
            }
            return $val;
        }
    }

    public function getColumn($col, $scope='fields')
    {
        if (!empty($this->config[$scope][$col]))
        {
            $conf =  $this->config[$scope][$col];
            $conf['column_index'] = $col;
        }
        else
        {
            $conf =  $this->resolveColumnByRelationName($col, $scope);
        }

        return $conf;
    }

    public function getFormConfig($prop='')
    {
        $form = null;
        $form =  $this->confParam('form');
        $tabbed = $this->confParam('form_tabbed');
        $form_array = [];
        $fields = $this->getFields();

        if (is_array($form))
        {
            if ($tabbed)
            {
                foreach ($form as $tab_alias=>$field_set) {
                    foreach ($field_set as $fname)
                    {
                        $form_array[$fname] = $fields[$fname];
                        $form_array[$fname]['tab'] = $tab_alias;
                    }

                }

            } else {

                foreach ($form as $fname) {
                    $form_array[$fname] = $fields[$fname];
                }
            }

        }

        if (empty($prop))
        {
            return $form_array;
        }
        else
        {
            return $form_array[$prop];
        }
    }

    public function getListConfig($prop='')
    {

        if (strpos($prop,'.') === false)
        {
            $conf = $this->confParam('scopes.' . $this->scope, null, false);
            if (empty($conf))
            {
                $conf = [];
            }
            if (empty($conf['list']))
            {
                $conf['list'] = [];
            }
            if (!empty($conf['multiselect']))
            {
                array_unshift($conf['list'],[ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox']);
            }
            else
            {
                array_unshift($conf['list'],[ "data"=> "id","orderable"=>false, 'invisible'=>true]);
            }
            if (!empty($conf['buttons']['single_edit'])
                || !empty($conf['buttons']['single_delete'])
                || !empty($conf['list_actions'])

            )
            {
                $conf['list'][] = [ "data"=>"actions", "orderable"=>false,'title'=>'  ', 'width'=>50, 'ctype'=>'actions'];
            }

            foreach($conf['list'] as $k=>$col)
            {

                if (empty($col['title']))
                {
                    $cdesc = $this->getColumn($col['data']);
                    if (!empty($cdesc['title'])) {
                        $conf['list'][$k]['title'] = $cdesc['title'];
                    }
                }
                if (!empty($col['hint']) && empty($col['hint']['index']))
                {
                    $conf['list'][$k]['hint']['index'] = $this->classViewName.'_'.$this->scope.'_'.$col['data'];
                }
                if (!empty($col['acl']) && !$this->app['skvn.cms']->checkAcl($col['acl'], 'r'))
                {
                    unset($conf['list'][$k]);
                }
            }

            if ($this->app['auth']->check())
            {
                $user = $this->app['auth']->user();
                if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
                {
                    $cols = $user->crudPrefFilterTableColumns($conf['list'], $this);
                    foreach($conf['list'] as $col)
                    {
                        if (!empty($col['invisible']))
                        {
                            $cols[] = $col;
                        }
                    }
                    $conf['all_columns'] = $conf['list'];
                    $conf['list'] = $cols;
                }
            }

            if (empty($prop))
            {
                return $conf;
            }
            else
            {
                if (isset($conf[$prop]))
                {
                    return $conf[$prop];
                }
            }
        }
        else
        {
            return $this->app['config']->get('crud.crud_'.$this->getTable().'.scopes.'.$this->scope.'.'.$prop);
        }

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

    function getDescribedColumnValue($col, $format = false)
    {
        $value = null;
        if ($relSpl = $this->resolveListRelation($col))
        {
            $rel = $relSpl[0];
            $attr = $relSpl[1];
            $value = $this->$rel->$attr;
        }
        else
        {
            if ($this->__isset($col))
            {
                $form_config = $this->getField($col);
                if ($form_config && !empty($form_config['type']))
                {
                    $form_config['name'] = $col;
                    $field = Field::create($this, $form_config);
                    $value = $field->getValueForList();
                }
                else
                {
                    $value = $this->$col;
                }
            }
            //FIXME
//            else
//            {
//                $meth = camel_case('get_' . $col);
//                if (method_exists($this, $meth))
//                {
//                    return $this->$meth();
//                }
//            }
        }
        if (!empty($format))
        {
            $method = "crudFormatValue" . camel_case($format);
            if (method_exists($this, $method))
            {
                $value = $this->$method($value);
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
        if (!isset($this->config['scopes'][$this->scope]))
        {
            $this->config['scopes'][$this->scope] = [];
        }
        $this->config['scope'] = $this->scope;
        if (!empty($this->config['fields']))
        {
            $form = !empty($this->config['scopes'][$this->scope]['form']) ? $this->flatFields($this->config['scopes'][$this->scope]['form'], !empty($this->config['scopes'][$this->scope]['form_tabbed'])) : [];
            if (!empty($this->config['fields']))
            {
                foreach ($this->config['fields'] as $name => $field)
                {
                    if (in_array($name, $form))
                    {
                        $this->markFillable($name, $field);
                    }
                }
            }
        }
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

    function isManyRelation($relation)
    {
        return in_array($relation, ['hasMany','belongsToMany', 'morphToMany', 'morphedByMany']);
    }

    function guessNewKey()
    {
        if (empty($this->guessed_id))
        {
            $this->guessed_id = $this->app['db']->table($this->getTable())->max($this->getKeyName())+1;
        }
        return $this->guessed_id;
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
            $conf['path'] = str_replace('%l1', substr($md5,0,2), $conf['path']);
            $conf['path'] = str_replace('%l2', substr($md5,2,2), $conf['path']);
            $conf['path'] = str_replace('%i3', str_pad($conf['instance_id'] % 1000, 3, '0', STR_PAD_LEFT), $conf['path']);
            $conf['path'] = str_replace('%id', $conf['instance_id'], $conf['path']);
            $conf['path'] = str_replace('%tbl', $conf['table'], $conf['path']);

            $conf['inline_path'] = str_replace('%l1', substr($md5,0,2), $conf['inline_path']);
            $conf['inline_path'] = str_replace('%l2', substr($md5,2,2), $conf['inline_path']);
            $conf['inline_path'] = str_replace('%i3', str_pad($conf['instance_id'] % 1000, 3, '0', STR_PAD_LEFT), $conf['inline_path']);
            $conf['inline_path'] = str_replace('%id', $conf['instance_id'], $conf['inline_path']);
            $conf['inline_path'] = str_replace('%tbl', $conf['table'], $conf['inline_path']);
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