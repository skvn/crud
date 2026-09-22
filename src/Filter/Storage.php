<?php

namespace Skvn\Crud\Filter;

use Illuminate\Foundation\Application;
use Skvn\Crud\Models\CrudModel;

class Storage
{
    protected $app;
    protected $storageType = 'session';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->storageType = $this->app['config']->get('crud_common.filter_storage');
    }

    public function getFormName()
    {
        if ($this->storageType === 'url') {
            return 'crud_filter_form_url';
        }
        return 'crud_filter_form';
    }

    public function getHttpMethod()
    {
        if ($this->storageType === 'url') {
            return 'GET';
        }
        return 'POST';
    }

    public function fill(CrudModel $model, $filters, $defaults, $input = [])
    {
        $getter = 'getFromStorage' . $this->storageType;
        $stored = $this->$getter($model, $filters);
        $store = [];
        $data = array_merge($defaults, $stored);
        foreach ($filters as $filter) {
            $filter->setValue($data[$filter->name] ?? null);
            if (! empty($input)) {
                $filter->pullFromData($input);
                $store[$filter->name] = $filter->getValue();
            }
        }
        if (! empty($store)) {
            $setter = 'setToStorage' . $this->storageType;
            $this->$setter($model, $store);
        }
    }

    public function getFromStorageUrl(CrudModel $model, $filters)
    {
        $data = $this->app['request']->all();
        $filterData = [];
        foreach ($filters as $filter) {
            $filterData[$filter->name] = $data[$filter->name] ?? null;
        }
        return $filterData;
    }

    public function getFromStorageSession(CrudModel $model, $filters)
    {
        return $this->app['session']->get($this->getStorageKey($model)) ?? [];
    }

    public function setToStorageSession(CrudModel $model, $data)
    {
        $this->app['session']->put($this->getStorageKey($model), $data);
    }

    public function setToStorageUrl(CrudModel $model, $data)
    {
    }

    public function getStorageKey(CrudModel $model)
    {
        return 'crud_filter_'.$model->classViewName.'_'.$model->scope;
    }


}