<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Radio extends Select
{
    


    function controlType():string
    {
        return "radio";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.radio";
    }

    function controlWidgetUrl():string
    {
        return "";
    }



    
} 