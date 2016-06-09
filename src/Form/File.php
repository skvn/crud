<?php namespace Skvn\Crud\Form;


class File extends Field {

    static $controlInfo = [
        'type' => "file",
        'template' => "crud::crud/fields/file.twig",
        'wizard_template' => "crud::wizard/blocks/fields/file.twig",
        'caption' => "File"
    ];

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