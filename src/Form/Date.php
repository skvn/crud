<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;


class Date extends Field implements WizardableField
{
    
    use CommonFieldWizardTrait;

    const TYPE = "date";

    public static function fieldDbType() {
        return 'date';
    }
    
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

    public static function callbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldKey];
    }

} 