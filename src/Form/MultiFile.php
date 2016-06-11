<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class MultiFile extends Field implements WizardableField, FormControl{


    use WizardCommonFieldTrait;
    use FormControlCommonTrait;


    function controlType():string
    {
        return "multi_file";
    }

    function controlTemplate():string
    {
        return "crud::crud/fields/multi_file.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/multi_file.twig";
    }


    function wizardCaption()
    {
        return "Multiple files";
    }



    function getExisting()
    {
        return $this->model->getAttach($this->getName());
    }


} 