<?php namespace Skvn\Crud\Form;


class Number extends Field {

    const TYPE = "number";


    static function controlTemplate()
    {
        return "crud::crud/fields/number.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/number.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Number input";
    }

    static function controlFiltrable()
    {
        return true;
    }



    protected $filtrable = true;

    function getValue()
    {
        if (!$this->value)
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getField());
            }
        }

        return $this->value;
    }

}