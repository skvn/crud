<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;

class Checkbox extends Field implements WizardableField, FormControl
{
    
    use WizardCommonFieldTrait;
    protected $filtrable = true;


    function controlType()
    {
        return "checkbox";
    }

    public function wizardDbType() {
        return 'boolean';
    }
    
    function controlTemplate()
    {
        return "crud::crud/fields/checkbox.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/checkbox.twig";
    }

    function controlWidgetUrl()
    {
        return "js/widgets/checkbox.js";
    }

    function wizardCaption()
    {
        return "Checkbox";
    }

    function wizardFiltrable()
    {
        return true;
    }



    function getValue()
    {
        if ($this->value === null)
        {
            $this->value = $this->model->getAttribute($this->getField());
        }

        return $this->value;
    }

}