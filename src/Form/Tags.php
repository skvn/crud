<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Tags extends Field implements  FormControl {


    use FormControlCommonTrait;


    function pullFromModel()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $dummyModel = new $class();
        $ids = $this->model->crudRelations->getIds($this->name);
        if (count($ids))
        {
            $collection = $class::findMany($ids);
            $this->value = $collection->pluck($dummyModel->confParam('title_field'));
        }
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->name]))
        {
            if (is_array($data[$this->name]))
            {
                $this->value = $data[$this->name];
            }
            else
            {
                $this->value = explode(",", $data[$this->name]);
            }
        }
        else
        {
            $this->value = [];
        }
    }

    function pushToModel()
    {
        $ids = [];
        $class = CrudModel :: resolveClass($this->config['model']);
        $dummyModel = new $class();
        if (!empty($this->value))
        {
            foreach ($this->value as $title)
            {
                $obj = $class::firstOrCreate([$dummyModel->confParam('title_field') => trim($title)]);
                $ids[] = $obj->getKey();
            }
        }
        $this->model->setAttribute($this->name, $ids);
    }


    function controlType():string
    {
        return "tags";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.tags";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/tags.js";
    }




} 