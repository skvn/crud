<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Contracts\FormControlFilterable;


class Number extends Field implements  FormControl, FormControlFilterable{


    use FormControlCommonTrait;

    function pullFromModel()
    {
        if (!in_array($this->name, $this->model->getHidden()))
        {
            $this->value = $this->model->getAttribute($this->field);
        }
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            return ['cond' => [$this->getFilterColumnName(), '=',  $this->value ]];
        }
    }


    function controlType():string
    {
        return "number";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.number";
    }




}