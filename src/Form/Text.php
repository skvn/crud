<?php namespace Skvn\Crud\Form;


class Text extends Field {


    function getValue()
    {
        if (is_null($this->value))
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getField());
            }
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
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