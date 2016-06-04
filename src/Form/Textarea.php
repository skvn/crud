<?php namespace Skvn\Crud\Form;


class TextArea extends Field {


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