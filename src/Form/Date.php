<?php

namespace LaravelCrud\Form;


class Date extends Field {


    function validateConfig()
    {
        return !empty($this->config['format']);
    }

    function getValue()
    {
        if (!$this->value)
        {
            $this->value = $this->form->crudObj->getAttribute($this->getName());
            if (!$this->value)
            {
                $this->value = time();
            }

        }

        return $this->value;
    }

    /**
     * @return array
     */
//    function getFilterCondition()
//    {
//
//        if (!empty($this->value)) {
//            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->name;
//            return ['cond' => [$col, 'LIKE', '%' . $this->value . '%']];
//        }
//
//
//    }

    function  getValueForDb()
    {

        return strtotime($this->getValue().' 23:59');
    }
} 