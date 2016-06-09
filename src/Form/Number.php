<?php namespace Skvn\Crud\Form;


class Number extends Field {

    static $controlInfo = [
        'type' => "number",
        'template' => "crud::crud/fields/number.twig",
        'wizard_template' => "crud::wizard/blocks/fields/number.twig",
        'caption' => "Number input",
        'filtrable' => true
    ];


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