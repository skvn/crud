<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class File extends Field implements WizardableField, FormControl
{

    use WizardCommonFieldTrait;
    use FormControlCommonTrait;


    function pullFromModel()
    {
        if ($this->model->getAttribute($this->field))
        {
            $this->value = $this->model->getAttach($this->name);
        }
    }

    function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }


    function controlType():string
    {
        return "file";
    }
    
    function controlTemplate():string
    {
        return "crud::crud/fields/file.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/file.twig";
    }


    function wizardCaption()
    {
        return "File";
    }





}