<?php namespace Skvn\Crud\Form;


class MultiFile extends Field {


    function getExisting()
    {
        return $this->model->getAttach($this->getName());
    }


} 