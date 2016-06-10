<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Wizard\Wizard;
use Skvn\Crud\Contracts\FormControl;


class DateTime extends Field implements WizardableField, FormControl
{

    use WizardCommonFieldTrait;


    function controlType()
    {
        return "date_time";
    }

    public function wizardDbType() {
        return 'dateTime';
    }

    function controlTemplate()
    {
        return "crud::crud/fields/date_time.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/date_time.twig";
    }


    function wizardCaption()
    {
        return "Date + Time";
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

    function wizardCallbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldKey];
    }

    
} 