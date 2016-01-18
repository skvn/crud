<?php namespace Skvn\Crud\Form;


class Number extends Field {


    function getValue()
    {
        if (!$this->value)
        {
            if (!in_array($this->getName(), $this->form->crudObj->getHidden()))
            {
                $this->value = $this->form->crudObj->getAttribute($this->getName());
            }
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value)) {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->name;
            return ['cond' => [$col, '=',  $this->value ]];
        }


    }
} 