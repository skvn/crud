<?php

namespace Skvn\Crud\Form;


class File extends Field {


    function getValue()
    {
        if (!$this->value)
        {
            if ($this->form->crudObj->getAttribute($this->getName()))
            {
                $this->value = $this->form->crudObj->getAttach($this->getName());
            }
        }

        return $this->value;
    }
} 