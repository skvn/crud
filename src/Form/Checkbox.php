<?php namespace Skvn\Crud\Form;


class Checkbox extends Field {


    function getValue()
    {
        if ($this->value === null)
        {
            $this->value = $this->form->crudObj->getAttribute($this->getName());
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value)) {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->name;
            return ['cond' => [$col, '=',  $this->value]];
        }


    }
} 