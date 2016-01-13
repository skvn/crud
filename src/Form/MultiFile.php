<?php

namespace Skvn\Crud\Form;


class MultiFile extends Field {


    function getExisting()
    {
        return $this->form->crudObj->getAttach($this->getName());
    }

} 