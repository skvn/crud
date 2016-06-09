<?php namespace Skvn\Crud\Form;


class DateTime extends Field {

    const TYPE = "date_time";

    static function controlTemplate()
    {
        return "crud::crud/fields/date_time.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/date_time.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Date + Time";
    }

    static function controlFiltrable()
    {
        return false;
    }


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