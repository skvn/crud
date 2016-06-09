<?php namespace Skvn\Crud\Form;


class TextArea extends Field {

    static $controlInfo = [
        'type' => "textarea",
        'template' => "crud::crud/fields/textarea.twig",
        'wizard_template' => "crud::wizard/blocks/fields/textarea.twig",
        'caption' => 'Textarea'
    ];


    function getValue()
    {
        if (!$this->value)
        {
            $this->value = $this->model->getAttribute($this->getField());
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value)) {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
            return ['cond' => [$col, 'LIKE', '%' . $this->value . '%']];
        }


    }
} 