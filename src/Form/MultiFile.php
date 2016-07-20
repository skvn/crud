<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class MultiFile extends Field implements  FormControl{


    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->name);
        return $this;
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    function controlType():string
    {
        return "multi_file";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.multi_file";
    }


    function getExisting()
    {
        return $this->model->getAttribute($this->getName());
    }


} 