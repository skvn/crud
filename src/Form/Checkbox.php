<?php namespace Skvn\Crud\Form;


class Checkbox extends Field {

    const TYPE = "checkbox";

    static function controlTemplate()
    {
        return "crud::crud/fields/checkbox.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/checkbox.twig";
    }

    static function controlWidgetUrl()
    {
        return "js/widgets/checkbox.js";
    }

    static function controlCaption()
    {
        return "Checkbox";
    }

    static function controlFiltrable()
    {
        return true;
    }

    protected $filtrable = true;


    function getValue()
    {
        if ($this->value === null)
        {
            $this->value = $this->model->getAttribute($this->getField());
        }

        return $this->value;
    }

}