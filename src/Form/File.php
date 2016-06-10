<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;

class File extends Field implements WizardableField
{

    use CommonFieldWizardTrait;

    const TYPE = "file";

    
    static function controlTemplate()
    {
        return "crud::crud/fields/file.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/file.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "File";
    }

    static function controlFiltrable()
    {
        return false;
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