<?php namespace Skvn\Crud\Form;


class File extends Field {


    function getValue()
    {
        if (!$this->value)
        {
            if ($this->model->getAttribute($this->getName()))
            {
                $this->value = $this->model->getAttach($this->getName());
            }
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        return false;
    }
} 