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
        $worker = 'fillFrom' . $this->storageType;
        $this->$worker($model, $filters, $defaults, $input);
    }

    private function fillFromSession(CrudModel $model, $filters, $defaults, $input)
    {
        $stored = $this->app['session']->get($this->getStorageKey($model)) ?? [];
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
            $this->app['session']->put($this->getStorageKey($model), $store);
        }
    }

    private function fillFromUrl(CrudModel $model, $filters, $defaults, $input)
    {
        $data = $defaults;
        $input = array_merge($input, $this->app['request']->all());
        foreach ($filters as $filter) {
            $filter->setValue($data[$filter->name] ?? null);
            if (! empty($input)) {
                $filter->pullFromData($input);
                $store[$filter->name] = $filter->getValue();
            }
        }
    }


    private function getStorageKey(CrudModel $model)
    {
        return 'crud_filter_'.$model->classViewName.'_'.$model->scope;
    }


}