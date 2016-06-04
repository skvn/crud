<?php namespace Skvn\Crud\Form;


class File extends Field {


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

}