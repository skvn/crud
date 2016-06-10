<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;


class File extends Field implements WizardableField, FormControl
{

    use WizardCommonFieldTrait;


    function controlType()
    {
        return "file";
    }
    
    function controlTemplate()
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



    function getValue()
    {
        if (!$this->value)
        {
            if ($this->model->getAttribute($this->getField()))
            {
                $this->value = $this->model->getAttach($this->getName());
            }
        }

        return $this->value;
    }

    function importValue($data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

}