<?php namespace Skvn\Crud\Form;



use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Image extends File implements  FormControl{




    function controlType():string
    {
        return "image";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.image";
    }





}