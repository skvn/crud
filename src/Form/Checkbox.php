<?php namespace Skvn\Crud\Form;


class Checkbox extends Field {

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