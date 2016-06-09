<?php namespace Skvn\Crud\Form;


class MultiFile extends Field {

    static $controlInfo = [
        'type' => "multi_file",
        'template' => "crud::crud/fields/multi_file.twig",
        'wizard_template' => "crud::wizard/blocks/fields/multi_file.twig",
        'caption' => "Multiple files"
    ];

    function getExisting()
    {
        return $this->model->getAttach($this->getName());
    }


} 