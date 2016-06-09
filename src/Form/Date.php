<?php namespace Skvn\Crud\Form;


class Date extends Field {

    const TYPE = "date";

    static function controlTemplate()
    {
        return "crud::crud/fields/date.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/date.twig";
    }

    static function controlWidgetUrl()
    {
        return "js/widgets/datetime.js";
    }

    static function controlCaption()
    {
        return "Date";
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
        if (is_null($this->value))
        {
            $this->value = $this->model->getAttribute($this->getField());
            if (!$this->value)
            {
                if ($this->isInt())
                {
                    $this->value = time();
                }
                else
                {
                    $this->value = (new \DateTime('now'));
                }
            }
        }

        return $this->value;
    }

    function getValueForList()
    {
        $v = $this->getValue();
        if ($this->isInt())
        {
            return date($this->config['format'], $v);
        }

        return $v;
    }

    private function isInt()
    {
        return (empty($this->config['db_type']) ||$this->config['db_type'] == 'int');
    }



    function  getValueForDb()
    {
        if ($this->isInt())
        {
            return strtotime($this->getValue() . ' 14:23');
        }
        else
        {
            return date('Y-m-d',strtotime($this->getValue()));
        }
    }
} 