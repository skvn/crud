<?php
namespace LaravelCrud\Traits;

trait PrefTrait
{
    protected $__prefs = null;


    function crudPrefFilterTableColumns($columns, $config)
    {
        $cols = array();
        foreach ($columns as $column)
        {
            if (!empty($column['ctype']) || $config->isColumnVisible($column['data']))
            {
                $cols[] = $column;
            }
        }
        if (empty($cols))
        {
            return $columns;
        }
        return $cols;
    }

    function crudPrefUI($type)
    {
        if ($this->app['request']->isMethod('post'))
        {
            $data = $this->app['request']->all();
            $scope = $this->crudPrefGetScope($data);
            $pref = $this->crudPrefGet($data['pref_type'], $scope);
            $pref['user_id'] = $this->id;
            $pref['scope'] = $scope;
            $pref['type_id'] = $data['pref_type'];
            $pref['pref'] = json_encode($data['pref']);
            $this->crudPrefSave($pref);
            return ['success' => true];
        }
        else
        {
            $obj = $this->app['skvn.crud']->getModelInstance($this->app['request']->get('model'), $this->app['request']->get('scope'));
//            $model = 'App\Model\\' . studly_case(\Request :: get('model'));
//            $obj = new $model();
//            $obj->config->setScope(\Request :: get('scope'));
            return $this->app['view']->make('crud::crud.choose_columns', ['crudObj' => $obj, 'pref_type' => $type]);
        }
    }

    protected function crudPrefGetScope($data)
    {
        if (empty($data['model']))
        {
            throw new \Exception("Model for preferences not found");
        }
        return $data['model'] . "::" . (empty($data['scope']) ? \LaravelCrud\CrudConfig :: DEFAULT_SCOPE : $data['scope']);
    }

    protected function crudPrefGet($type, $scope)
    {
        foreach ($this->crudPrefAll() as $pref)
        {
            if ($pref->type_id == $type && $pref->scope == $scope)
            {
                return get_object_vars($pref);
            }
        }
        return [];
    }

    function crudPrefGetVal($type, $scope)
    {
        $pref = $this->crudPrefGet($type, $scope);
        if (!empty($pref) && !empty($pref['pref']))
        {
            return json_decode($pref['pref'], true);
        }
        return null;
    }

    function crudPrefForModel($type, $model)
    {
        $scope = $this->crudPrefGetScope(['model' => $model->attrModelName(), 'scope' => $model->config->getScope()]);
        return $this->crudPrefGetVal($type, $scope);
    }

    protected function crudPrefAll()
    {
        if (is_null($this->__prefs))
        {
            $this->__prefs = $this->app['db']->table('crud_user_pref')->where('user_id', $this->id)->get();
        }
        return $this->__prefs;
    }

    protected function crudPrefSave($pref)
    {
        if (!empty($pref['id']))
        {
            $id = $pref['id'];
            unset($pref['id']);
            $this->app['db']->table('crud_user_pref')->where('id', $id)->update($pref);
        }
        else
        {
            $this->app['db']->table('crud_user_pref')->insert($pref);
        }
    }
}