<?php

namespace Skvn\Crud\Form;


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
                if (empty($this->config['db_type']) ||$this->config['db_type'] == 'int' ) {
                    $this->value = time();
                } else {
                    $this->value = (new \DateTime('now'));
                }

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
        if (empty($this->config['db_type']) ||$this->config['db_type'] == 'int' ) {
            return strtotime($this->getValue() . ' 23:59');
        } else {
            return date('Y-m-d',strtotime($this->getValue()));
        }
    }
} 