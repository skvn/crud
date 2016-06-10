<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;


class Date extends Field implements FormControl{


    function controlType()
    {
        return "date";
    }

    function controlTemplate()
    {
        return "crud::crud/fields/date.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/date.twig";
    }

    function controlWidgetUrl()
    {
        return "js/widgets/datetime.js";
    }

    function widgetCaption()
    {
        return "Date";
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

    public function wizardCallbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldKey];
    }


    function  prepareValueForDb($value)
    {
        if ($this->isInt())
        {
            return strtotime($value . ' 14:23');
        }
        else
        {
            return date('Y-m-d',strtotime($this->value));
        }
    }

} 