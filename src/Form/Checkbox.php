<?php namespace Skvn\Crud\Form;


class Checkbox extends Field {


    static $controlInfo = [
        'type' => "checkox",
        'template' => "crud::crud/fields/checkbox.twig",
        'wizard_template' => "crud::wizard/blocks/fields/checkbox.twig",
        'caption' => "Checkbox",
        'filtrable' => true
    ];

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