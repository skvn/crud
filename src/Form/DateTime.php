<?php namespace Skvn\Crud\Form;


class DateTime extends Field {

    static $controlInfo = [
        'type' => "date_time",
        'template' => "crud::crud/fields/date_time.twig",
        'wizard_template' => "crud::wizard/blocks/fields/date_time.twig",
        'caption' => "Date + Time"
    ];

    function validateConfig()
    {
        return !empty($this->config['format']);
    }

    function getValue()
    {
        if (!$this->value)
        {
            $this->value = $this->model->getAttribute($this->getField());
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


    function getValueForList()
    {

        if (empty($this->config['db_type']) ||$this->config['db_type'] == 'int' ) {
            return date($this->config['format'], $this->getValue());
        } else {
            return date($this->config['format'], strtotime($this->getValue()));
        }
    }

    function  getValueForDb()
    {
        if (empty($this->config['db_type']) ||$this->config['db_type'] == 'int' ) {
            return strtotime($this->getValue());
        } else {
            return date('Y-m-d H:i:s',strtotime($this->getValue()));
        }
    }
} 