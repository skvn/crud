<?php namespace Skvn\Crud\Form;


class Text extends Field {


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
        if (!empty($this->value))
        {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->name;
            $value = str_replace(['*', '?'], ['%', '_'], $this->value);
            if (strpos($value, "~") === 0)
            {
                return ['cond' => [$col, 'NOT LIKE',  substr($value, 1) ]];
            }
            else
            {
                return ['cond' => [$col, 'LIKE',  $value ]];
            }
        }


    }
} 