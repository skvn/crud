<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Textarea extends Field implements  FormControl, FormControlFilterable{


    use FormControlCommonTrait;


    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            return ['cond' => [$this->getFilterColumnName(), 'LIKE', '%' . $this->value . '%']];
        }

    }


    function controlType():string
    {
        return "textarea";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.textarea";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/editor.js";
    }




    }

