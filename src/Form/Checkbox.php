<?php namespace Skvn\Crud\Form;



use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;

class Checkbox extends Field implements  FormControl, FormControlFilterable
{

    use FormControlCommonTrait;

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            return ['cond' => [$this->getFilterColumnName(), '=',  $this->value ]];
        }
    }

    function controlType():string
    {
        return "checkbox";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.checkbox";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/checkbox.js";
    }




}