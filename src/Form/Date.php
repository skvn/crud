<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Date extends Field implements FormControl{

    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);
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

    function getOutputValue():string
    {
        if ($this->isInt())
        {
            return date($this->config['format'], $this->value);
        }

        return $this->value;
    }


    function controlType():string
    {
        return "date";
    }

    function controlTemplate():string
    {
        return "crud::crud/fields/date.twig";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/datetime.js";
    }

    function controlValidateConfig():bool
    {
        return !empty($this->config['format']);
    }


    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/date.twig";
    }

    function wizardCaption()
    {
        return "Date";
    }

    private function isInt()
    {
        return (empty($this->config['db_type']) ||$this->config['db_type'] == 'int');
    }


    public function wizardCallbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldKey];
    }



} 