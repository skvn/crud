<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Image extends File implements WizardableField, FormControl{


    use WizardCommonFieldTrait;


    function controlType():string
    {
        return "image";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.image";
    }

    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.image";
    }


    function wizardCaption()
    {
        return "Image";
    }




}