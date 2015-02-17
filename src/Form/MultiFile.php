<?php

namespace LaravelCrud\Form;


class MultiFile extends Field {


    function getExisting()
    {
        return $this->form->crudObj->getAttach($this->getName());
    }

} 